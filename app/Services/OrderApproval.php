<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Jobs\SubmitOrderToMart;
use App\Models\Order;
use App\Models\User;
use App\Services\Mart\MartClient;
use App\Services\Mart\MartException;
use Illuminate\Support\Facades\DB;

/** Every admin-driven status change. Transitions: specs/001-reseller-storefront/data-model.md */
class OrderApproval
{
    public function __construct(private Checkout $checkout, private MartClient $mart) {}

    /**
     * Agreed prices are kept while they cover cost. If the customer's total would change,
     * CheckoutException carries the new figures and nothing is saved until $customerAccepted.
     *
     * @param  array<int, float>  $prices  new unit price per order item id
     *
     * @throws CheckoutException
     */
    public function approve(Order $order, ?User $admin = null, array $prices = [], ?bool $freeShipping = null, bool $customerAccepted = false): void
    {
        $this->locked($order, fn () => $this->saveApproval($order, $admin, $prices, $freeShipping, $customerAccepted));

        // Outside the lock: the job takes the same one.
        SubmitOrderToMart::dispatch($order->id);
    }

    private function saveApproval(Order $order, ?User $admin, array $prices, ?bool $freeShipping, bool $customerAccepted): void
    {
        $this->assertStatus($order, [OrderStatus::PendingReview, OrderStatus::SendFailed]);

        if ($order->attention_reason === 'conflict') {
            throw new CheckoutException(__('admin.errors.check_in_mart'));
        }

        $order->load('items.product');

        foreach ($order->items as $item) {
            if (isset($prices[$item->id])) {
                $item->unit_price = round((float) $prices[$item->id], 2);
            }

            if (! $item->product?->isSellable()) {
                throw new CheckoutException(__('admin.errors.product_gone', ['name' => $item->name]));
            }

            $item->unit_cost = $item->product->cost_price;

            if ((float) $item->unit_price < (float) $item->unit_cost) {
                throw new CheckoutException(__('admin.errors.below_cost', ['name' => $item->name, 'cost' => $item->unit_cost]));
            }
        }

        $freeShipping ??= $order->free_shipping;

        try {
            $figures = $this->checkout->martFigures($order, $freeShipping);
        } catch (MartException $e) {
            if ($this->checkout->isStale($e)) {
                $this->checkout->refreshProducts($order->items->pluck('product_id')->all());
            }

            throw new CheckoutException($e->retryable()
                ? __('admin.errors.mart_unreachable')
                : __('admin.errors.mart_refused', ['code' => $e->errorCode, 'message' => $e->getMessage()]));
        }

        if (abs($figures['customer_total'] - (float) $order->customer_total) >= 0.01 && ! $customerAccepted) {
            throw new CheckoutException(__('admin.errors.total_changed', [
                'old' => $order->customer_total,
                'new' => number_format($figures['customer_total'], 2),
            ]), $figures);
        }

        DB::transaction(function () use ($order, $figures, $freeShipping, $admin) {
            $order->items->each->save();
            $order->fill($figures)->fill([
                'status' => OrderStatus::Approved,
                'request_body' => $this->checkout->body($order, $freeShipping),
                'approved_by' => $admin?->id,
                'approved_at' => now(),
                'attention_reason' => null,
                'last_error' => null,
                'send_attempts' => 0,
                'next_attempt_at' => null,
            ])->save();
        });
    }

    public function reject(Order $order): void
    {
        $this->locked($order, function () use ($order) {
            $this->assertStatus($order, [OrderStatus::PendingReview, OrderStatus::SendFailed]);

            if ($order->attention_reason === 'conflict') {
                throw new CheckoutException(__('admin.errors.check_in_mart'));
            }

            $order->update(['status' => OrderStatus::Rejected, 'attention_reason' => null, 'next_attempt_at' => null]);
        });
    }

    /** Withdraw an order that is still retrying, only once Mart confirms it does not have it. */
    public function stopRetrying(Order $order): void
    {
        $this->locked($order, function () use ($order) {
            $this->assertStatus($order, [OrderStatus::Approved]);

            if ($mart = $this->find($order)) {
                $order->applyMart($mart);

                return;
            }

            $order->update([
                'status' => OrderStatus::PendingReview,
                'request_body' => null,
                'approved_by' => null,
                'approved_at' => null,
                'next_attempt_at' => null,
            ]);
        });
    }

    /** After an idempotency conflict: link Mart's order, or release the block if it has none. */
    public function checkInMart(Order $order): void
    {
        $this->locked($order, function () use ($order) {
            $this->assertStatus($order, [OrderStatus::SendFailed]);

            if ($mart = $this->find($order)) {
                $order->attention_reason = 'found_in_mart';
                $order->applyMart($mart);

                return;
            }

            $order->update(['attention_reason' => null, 'request_body' => null]);
        });
    }

    /** The owner has dealt with the warning. */
    public function markHandled(Order $order): void
    {
        $order->update(['attention_reason' => null]);
    }

    /** One action at a time per order, always on what the database says now. */
    private function locked(Order $order, callable $change): void
    {
        $lock = SubmitOrderToMart::lock($order->id);

        if (! $lock->get()) {
            throw new CheckoutException(__('admin.errors.sending_now'));
        }

        try {
            $order->refresh();
            $change();
        } finally {
            $lock->release();
        }
    }

    public function cancel(Order $order): void
    {
        $this->assertStatus($order, [OrderStatus::Submitted]);

        $this->call(fn () => $this->mart->post('orders/cancel/'.$order->reference));

        $order->update(['status' => OrderStatus::Cancelled, 'attention_reason' => null]);
    }

    public function note(Order $order, string $note, bool $sendToMart): void
    {
        if ($sendToMart) {
            $this->assertStatus($order, [OrderStatus::Submitted]);
            $this->call(fn () => $this->mart->post('orders/notes/'.$order->reference, ['note' => $note]));
        }

        $order->update(['admin_note' => $note]);
    }

    private function find(Order $order): ?array
    {
        try {
            return $this->mart->get('orders/'.$order->reference)['data']['order'];
        } catch (MartException $e) {
            if ($e->errorCode === 'order_not_found') {
                return null;
            }

            throw new CheckoutException(__('admin.errors.mart_unreachable'));
        }
    }

    private function call(callable $call): void
    {
        try {
            $call();
        } catch (MartException $e) {
            throw new CheckoutException(__('admin.errors.mart_refused', ['code' => $e->errorCode, 'message' => $e->getMessage()]));
        }
    }

    private function assertStatus(Order $order, array $allowed): void
    {
        if (! in_array($order->status, $allowed, true)) {
            throw new CheckoutException(__('admin.errors.wrong_status'));
        }
    }
}
