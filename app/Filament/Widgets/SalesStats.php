<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class SalesStats extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    /** Orders that count as sales in the chosen period. */
    public static function counted(?string $days): Builder
    {
        return Order::query()
            ->whereNotIn('status', [OrderStatus::Rejected, OrderStatus::Cancelled])
            ->when((int) $days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays((int) $days)));
    }

    public static function figures(?string $days): array
    {
        return [
            'orders' => self::counted($days)->count(),
            'sales' => (float) self::counted($days)->sum('customer_total'),
            'expected' => (float) self::counted($days)->sum('expected_net'),
            'delivered' => (float) self::counted($days)->whereIn('earning_state', ['held', 'available'])->sum('net_earning'),
        ];
    }

    protected function getStats(): array
    {
        $f = self::figures($this->pageFilters['days'] ?? '30');
        $money = fn (float $v) => number_format($v, 2).' ₪';

        return [
            Stat::make(__('admin.orders'), $f['orders']),
            Stat::make(__('admin.sales'), $money($f['sales'])),
            Stat::make(__('admin.expected_net'), $money($f['expected'])),
            Stat::make(__('admin.delivered_net'), $money($f['delivered'])),
        ];
    }
}
