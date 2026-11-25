<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;

class TopProducts extends Widget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.top-products';

    protected function getViewData(): array
    {
        return [
            'products' => OrderItem::query()
                ->whereIn('order_id', SalesStats::counted($this->pageFilters['days'] ?? '30')->select('id'))
                ->selectRaw('product_id, MAX(name) as name, SUM(quantity) as units')
                ->groupBy('product_id')->orderByDesc('units')->limit(5)->get(),
        ];
    }
}
