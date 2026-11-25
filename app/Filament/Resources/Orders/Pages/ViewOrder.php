<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\CheckoutException;
use App\Services\OrderApproval;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        $in = fn (OrderStatus ...$statuses) => fn (Order $record) => in_array($record->status, $statuses, true);
        $conflict = fn (Order $record) => $record->attention_reason === 'conflict';
        $reviewable = fn (Order $record) => in_array($record->status, [OrderStatus::PendingReview, OrderStatus::SendFailed], true) && ! $conflict($record);

        return [
            Action::make('approve')->label(__('admin.approve'))->color('success')
                ->visible($reviewable)
                ->schema(fn (Order $record) => [
                    ...$record->items->map(fn ($item) => TextInput::make('price_'.$item->id)
                        ->label(__('admin.unit_price').': '.$item->name)->numeric()->required()->minValue(0))->all(),
                    Toggle::make('free_shipping')->label(__('admin.free_shipping')),
                    Checkbox::make('accepted')->label(__('admin.customer_accepted')),
                ])
                ->fillForm(fn (Order $record) => $record->items->mapWithKeys(fn ($item) => ['price_'.$item->id => $item->unit_price])->all()
                    + ['free_shipping' => $record->free_shipping, 'accepted' => false])
                ->action(function (Order $record, array $data, Action $action) {
                    $prices = $record->items->mapWithKeys(fn ($item) => [$item->id => $data['price_'.$item->id]])->all();

                    $this->attempt($action, fn (OrderApproval $approval) => $approval->approve(
                        $record, auth()->user(), $prices, (bool) $data['free_shipping'], (bool) $data['accepted'],
                    ));
                }),

            Action::make('reject')->label(__('admin.reject'))->color('danger')->requiresConfirmation()
                ->visible($reviewable)
                ->action(fn (Order $record, Action $action) => $this->attempt($action, fn (OrderApproval $a) => $a->reject($record))),

            Action::make('stopRetrying')->label(__('admin.stop_retrying'))->color('warning')->requiresConfirmation()
                ->visible($in(OrderStatus::Approved))
                ->action(fn (Order $record, Action $action) => $this->attempt($action, fn (OrderApproval $a) => $a->stopRetrying($record))),

            Action::make('checkInMart')->label(__('admin.check_in_mart'))
                ->visible($conflict)
                ->action(fn (Order $record, Action $action) => $this->attempt($action, fn (OrderApproval $a) => $a->checkInMart($record))),

            Action::make('cancelOrder')->label(__('admin.cancel_order'))->color('danger')->requiresConfirmation()
                ->visible($in(OrderStatus::Submitted))
                ->action(fn (Order $record, Action $action) => $this->attempt($action, fn (OrderApproval $a) => $a->cancel($record))),

            Action::make('note')->label(__('admin.note'))->color('gray')
                ->schema(fn (Order $record) => [
                    Textarea::make('note')->label(__('admin.note'))->required()->maxLength(1000),
                    Toggle::make('send')->label(__('admin.send_to_mart'))->visible($record->status === OrderStatus::Submitted),
                ])
                ->fillForm(fn (Order $record) => ['note' => $record->admin_note])
                ->action(fn (Order $record, array $data, Action $action) => $this->attempt(
                    $action, fn (OrderApproval $a) => $a->note($record, $data['note'], (bool) ($data['send'] ?? false)),
                )),
        ];
    }

    private function attempt(Action $action, callable $run): void
    {
        try {
            $run(app(OrderApproval::class));
        } catch (CheckoutException $e) {
            Notification::make()->danger()->title($e->getMessage())->persistent()->send();
            $action->halt();
        }

        Notification::make()->success()->title(__('admin.done'))->send();
        $this->record->refresh()->load('items');
    }
}
