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
                $response = $mart->get('orders', array_filter([
                    // Overlap so nothing falls between two runs.
                    'updated_since' => $since ? Carbon::parse($since)->subMinutes(10)->toIso8601String() : null,
                    'order' => 'asc',
                    'limit' => 100,
                    'page' => $page++,
                ]));

                foreach ($response['data']['orders'] as $summary) {
                    $reference = $summary['partner_reference'];
                    $order = Order::where('reference', $reference)->first();

                    if (! $order || isset($done[$reference])) {
                        continue;
                    }

                    $done[$reference] = true;
                    $detail = $mart->get('orders/'.$reference)['data']['order'];

                    if (! in_array($order->status, [OrderStatus::Submitted, OrderStatus::Cancelled], true)) {
                        $order->attention_reason = 'found_in_mart';
                    }

                    $order->applyMart($detail);
                }
            } while ($response['meta']['pagination']['has_next'] ?? false);
        } catch (MartException $e) {
            // Keep the old mark: the next run starts from the same point.
            $this->error($e->errorCode);

            return self::FAILURE;
        }

        Setting::set('orders_synced_at', $started->toIso8601String());
        $this->info('Updated '.count($done).' orders.');

        return self::SUCCESS;
    }
}
