<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Jobs\SubmitOrderToMart;
use App\Models\Order;
use Illuminate\Console\Command;

class ResendOrders extends Command
{
    protected $signature = 'mart:resend-orders';

    protected $description = 'Retry approved orders that Mart has not confirmed';

    public function handle(): int
    {
        Order::where('status', OrderStatus::Approved)
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->where('approved_at', '<=', now()->subMinute())
            ->pluck('id')
            ->each(fn (int $id) => SubmitOrderToMart::dispatch($id));

        return self::SUCCESS;
    }
}
