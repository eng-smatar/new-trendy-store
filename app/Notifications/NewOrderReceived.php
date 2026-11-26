<?php

namespace App\Notifications;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Tells an administrator an order is waiting for review, in their panel language. */
class NewOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Seconds between tries, for a mail server that is briefly down. */
    public array $backoff = [60, 300];

    public function __construct(public Order $order) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // No customer name, phone or address: those stay behind the admin sign-in.
        return (new MailMessage)
            ->subject(__('admin.mail.subject', ['reference' => $this->order->reference]))
            ->view('mail.new-order', [
                'order' => $this->order,
                'url' => OrderResource::getUrl('view', ['record' => $this->order], panel: 'admin'),
            ]);
    }
}
