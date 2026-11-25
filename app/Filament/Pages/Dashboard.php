<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('days')->label(__('admin.period'))->default('30')->selectablePlaceholder(false)->options([
                '7' => __('admin.last_days', ['days' => 7]),
                '30' => __('admin.last_days', ['days' => 30]),
                '90' => __('admin.last_days', ['days' => 90]),
                '0' => __('admin.all_time'),
            ]),
        ]);
    }
}
