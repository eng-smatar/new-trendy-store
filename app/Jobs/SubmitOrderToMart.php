<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Mart\MartClient;
use App\Services\Mart\MartException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/** One send attempt. mart:resend-orders retries anything still "approved". */
class SubmitOrderToMart implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $orderId) {}

    public static function lock(int $orderId)
    {
        return Cache::lock("order-submit:$orderId", 120);
    }

    public function handle(MartClient $mart): void
    {
        $lock = self::lock($this->orderId);

        if (! $lock->get()) {
            return;
        }

        try {
            $order = Order::find($this->orderId);

            if ($order?->status !== OrderStatus::Approved || ! $order->request_body) {
                return;
            }

            if ($order->next_attempt_at?->isFuture()) {
                return;
            }

            $this->send($mart, $order);
        } finally {
            $lock->release();
        }
    }

    private function send(MartClient $mart, Order $order): void
    {
        $order->increment('send_attempts');

        try {
            // The stored body is resent unchanged: Mart de-duplicates on partner_reference.
            $response = $mart->post('orders', $order->request_body);
        } catch (MartException $e) {
            $order->last_error = $e->errorCode.': '.$e->getMessage();

            if ($e->retryable()) {
                $order->next_attempt_at = now()->addSeconds(max($e->retryAfter, min(60 * 2 ** min($order->send_attempts - 1, 6), 3600)));
            } else {
                $order->status = OrderStatus::SendFailed;
                $order->attention_reason = $e->errorCode === 'idempotency_conflict' ? 'conflict' : null;
            }

            $order->save();

            return;
        }

        $order->applyMart($response['data']['order'] ?? []);
    }
}
