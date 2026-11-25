<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')->label(__('admin.refresh_now'))->icon('heroicon-o-arrow-path')
                ->action(function () {
                    Artisan::queue('mart:sync-towns');
                    Artisan::queue('mart:sync-catalog');
                    Notification::make()->success()->title(__('admin.refresh_queued'))->send();
                }),
        ];
    }
}
