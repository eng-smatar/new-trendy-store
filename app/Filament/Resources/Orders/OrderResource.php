<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $recordTitleAttribute = 'reference';

    public static function getModelLabel(): string
    {
        return __('admin.order');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.orders');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Order::where('status', OrderStatus::PendingReview)->count();

        return $pending ? (string) $pending : null;
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('orders.status.pending_review');
    }

    private static function badges(): array
    {
        return [
            'unverified' => fn (Order $r) => $r->quote_verified ? null : __('admin.unverified'),
            'attention' => fn (Order $r) => $r->attention_reason ? __('admin.attention.'.$r->attention_reason) : null,
        ];
    }

    public static function table(Table $table): Table
    {
        $badges = self::badges();

        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('reference')->label(__('admin.reference'))->searchable(),
                TextColumn::make('first_name')->label(__('admin.customer'))
                    ->formatStateUsing(fn (Order $record) => $record->first_name.' '.$record->last_name)->searchable(['first_name', 'last_name']),
                TextColumn::make('phone')->label(__('admin.phone'))->searchable(),
                TextColumn::make('status')->label(__('admin.status'))->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())->color(fn (OrderStatus $state) => $state->color()),
                TextColumn::make('unverified')->label('')->badge()->color('warning')->state($badges['unverified']),
                TextColumn::make('attention')->label('')->badge()->color('danger')->state($badges['attention']),
                TextColumn::make('customer_total')->label(__('admin.total'))->money('ILS'),
                TextColumn::make('expected_net')->label(__('admin.expected_net'))->money('ILS'),
                TextColumn::make('created_at')->label(__('admin.created_at'))->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('admin.status'))
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $badges = self::badges();
        $text = fn (string $name, ?string $label = null) => TextEntry::make($name)->label($label ?? __("admin.$name"))->placeholder('—');
        $money = fn (string $name) => $text($name)->money('ILS');

        return $schema->components([
            Section::make(__('admin.order'))->columns(3)->columnSpanFull()->schema([
                $text('reference')->copyable(),
                TextEntry::make('status')->label(__('admin.status'))->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())->color(fn (OrderStatus $state) => $state->color()),
                $text('created_at')->dateTime('Y-m-d H:i'),
                $text('unverified', '')->badge()->color('warning')->state($badges['unverified'])->hidden(fn (Order $r) => $r->quote_verified),
                $text('attention', '')->badge()->color('danger')->state($badges['attention'])->hidden(fn (Order $r) => ! $r->attention_reason),
                $text('last_error')->columnSpanFull()->hidden(fn (Order $r) => ! $r->last_error),
                $text('mart_status_name'),
                $text('earning_state')->formatStateUsing(fn (string $state) => __("admin.earning.$state")),
                $text('admin_note'),
            ]),
            Section::make(__('admin.customer'))->columns(3)->columnSpanFull()->schema([
                $text('name', __('admin.customer'))->state(fn (Order $r) => $r->first_name.' '.$r->last_name),
                $text('phone')->copyable(),
                $text('email'),
                $text('gender')->formatStateUsing(fn (string $state) => __("admin.$state")),
                $text('address', __('admin.address'))->state(fn (Order $r) => $r->fullAddress())->columnSpan(2),
                $text('notes')->columnSpanFull(),
            ]),
            Section::make(__('admin.items'))->columnSpanFull()->schema([
                RepeatableEntry::make('items')->hiddenLabel()->columns(5)->schema([
                    $text('name'),
                    $text('options_label'),
                    $text('quantity'),
                    $money('unit_price'),
                    $money('unit_cost'),
                ]),
            ]),
            Section::make(__('admin.totals'))->columns(4)->columnSpanFull()->schema([
                $money('products_total'),
                $money('shipping_fee'),
                $text('free_shipping')->formatStateUsing(fn ($state) => $state ? __('admin.yes') : __('admin.no')),
                $money('customer_total'),
                $money('expected_net'),
                $money('net_earning'),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
