<?php

namespace App\Filament\Widgets;

use App\Models\Setting;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class SyncStatus extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $success = Setting::get('sync_last_success');
        $success = $success ? Carbon::parse($success) : null;
        $error = Setting::get('sync_last_error');
        $stale = ! $success || $success->lt(now()->subDay());

        return [
            Stat::make(__('admin.last_sync'), $success?->diffForHumans() ?? __('admin.never'))
                ->description($stale ? __('admin.sync_stale') : __('admin.sync_ok'))
                ->color($stale ? 'danger' : 'success'),
            Stat::make(__('admin.last_sync_error'), $error ? Carbon::parse($error['at'])->diffForHumans() : __('admin.none'))
                ->description($error ? str($error['message'])->limit(120) : '')
                ->color($error && (! $success || Carbon::parse($error['at'])->gt($success)) ? 'danger' : 'gray'),
        ];
    }
}
