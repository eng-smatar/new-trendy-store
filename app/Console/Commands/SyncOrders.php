<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Services\Mart\MartClient;
use App\Services\Mart\MartException;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncOrders extends Command
{
    protected $signature = 'mart:sync-orders';

    protected $description = 'Keep local order statuses and earnings in step with Mart';

    public function handle(MartClient $mart): int
    {
        $started = now();
        $since = Setting::get('orders_synced_at');
        $page = 1;
        $done = [];

        try {
            do {
                $response = $mart->getPatiently('orders', array_filter([
                    // Overlap so nothing falls between two runs.
                    'updated_since' => $since ? Carbon::parse($since)->subMinutes(10)->toIso8601String() : null,
                    'order' => 'asc',
                    'limit' => 100,
                    'page' => $page++,
                ]));

                foreach ($response['data']['orders'] as $summary) {
                    $reference = $summary['partner_reference'];
                    $order = Order::where('reference', $reference)->first();

                    if ($order && ! isset($done[$reference])) {
                        $done[$reference] = true;
                        $this->update($mart, $order);
                    }

                    // Remember how far we got, so a failed run resumes here instead of starting over.
                    if (isset($summary['updated_at'])) {
                        Setting::set('orders_synced_at', $summary['updated_at']);
                    }
                }
            } while ($response['meta']['pagination']['has_next'] ?? false);
        } catch (MartException $e) {
            $this->error($e->errorCode);

            return self::FAILURE;
        }

        Setting::set('orders_synced_at', $started->toIso8601String());
        $this->info('Updated '.count($done).' orders.');

        return self::SUCCESS;
    }

    private function update(MartClient $mart, Order $order): void
    {
        try {
            $detail = $mart->getPatiently('orders/'.$order->reference)['data']['order'];
        } catch (MartException $e) {
            // Only an order Mart no longer has may be skipped; anything else stops the run to try again.
            if ($e->errorCode !== 'order_not_found') {
                throw $e;
            }

            report($e);

            return;
        }

        if (! in_array($order->status, [OrderStatus::Submitted, OrderStatus::Cancelled], true)) {
            $order->attention_reason = 'found_in_mart';
        }

        $order->applyMart($detail);
    }
}
