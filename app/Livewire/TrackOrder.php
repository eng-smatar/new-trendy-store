<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\ArabicText;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.store')]
class TrackOrder extends Component
{
    public string $reference = '';

    public string $phone = '';

    public string $notice = '';

    #[Locked]
    public ?array $result = null;

    public function find(): void
    {
        $this->result = null;
        $this->notice = '';
        $this->validate(['reference' => ['required', 'string', 'max:64'], 'phone' => ['required', 'string', 'max:40']]);

        if (! RateLimiter::attempt('track:'.request()->ip(), 10, fn () => true)) {
            $this->notice = __('store.too_many');

            return;
        }

        $digits = fn (string $v) => preg_replace('/\D/', '', ArabicText::digits($v));
        $order = Order::where('reference', trim($this->reference))->first();

        // One message for every mismatch, so nothing about other orders leaks.
        if (! $order || $digits($order->phone) !== $digits($this->phone)) {
            $this->notice = __('store.track_not_found');

            return;
        }

        $this->result = ['reference' => $order->reference, 'status' => $order->status === OrderStatus::Submitted && $order->mart_status_name ? $order->mart_status_name : $order->status->label(), 'total' => (float) $order->customer_total];
    }

    public function render()
    {
        return view('livewire.track-order')->title(__('store.track'));
    }
}
