<?php

namespace App\Livewire;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Models\Town;
use App\Services\Cart;
use App\Services\Checkout;
use App\Services\CheckoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.store')]
class CheckoutPage extends Component
{
    public string $first_name = '';

    public string $last_name = '';

    public string $phone = '';

    public string $email = '';

    public string $gender = '';

    public string $district_id = '';

    public string $town_id = '';

    public string $address_line1 = '';

    public string $notes = '';

    /** The total the customer is looking at; confirming only goes through if it still holds. */
    #[Locked]
    public ?float $shownTotal = null;

    /** Mart's figures when they differ from the local ones. */
    #[Locked]
    public ?array $confirmed = null;

    public string $notice = '';

    public function updatedDistrictId(): void
    {
        $this->town_id = '';
        $this->confirmed = null;
        $this->notice = '';
    }

    public function updatedTownId(): void
    {
        $this->confirmed = null;
        $this->notice = '';
    }

    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^\+?[0-9\s\-()]{7,}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'district_id' => ['required', Rule::exists('towns', 'district_id')],
            'town_id' => ['required', Rule::exists('towns', 'id')->where('district_id', $this->district_id)->where('available', true)],
            'address_line1' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function place(Cart $cart, Checkout $checkout)
    {
        $this->notice = '';

        // Arabic-Indic digits are common on Arabic keyboards.
        $this->phone = trim(strtr($this->phone, array_combine(
            [...mb_str_split('٠١٢٣٤٥٦٧٨٩'), ...mb_str_split('۰۱۲۳۴۵۶۷۸۹')],
            [...range(0, 9), ...range(0, 9)],
        )));

        $data = $this->validate();

        if (! Setting::get('ordering_enabled')) {
            return $this->notice = Setting::get('closed_message');
        }

        $phoneKey = 'order-phone:'.preg_replace('/\D/', '', $data['phone']);
        $ipKey = 'order-ip:'.request()->ip();

        if (RateLimiter::tooManyAttempts($phoneKey, 3) || RateLimiter::tooManyAttempts($ipKey, 10)
            || ! RateLimiter::attempt('order-try:'.request()->ip(), 10, fn () => true)) {
            return $this->notice = __('store.too_many');
        }

        $lock = Cache::lock('checkout:'.session()->getId(), 60);

        if (! $lock->get()) {
            return null;
        }

        try {
            $lines = $cart->lines();

            if ($lines->isEmpty() || $lines->contains('available', false)) {
                return $this->redirect(url('/cart'));
            }

            if (Cart::total($lines) < (float) Setting::get('min_order_amount')) {
                return $this->notice = __('store.min_order', ['amount' => Setting::get('min_order_amount')]);
            }

            $town = Town::findOrFail($data['town_id']);
            $order = $this->draft($lines, $town);
            $order->fill(collect($data)->except(['district_id', 'town_id'])->map(fn ($v) => $v === '' ? null : $v)->all());

            try {
                $figures = $checkout->quote($order, $town);
            } catch (CheckoutException $e) {
                return $this->notice = $e->getMessage();
            }

            if (abs($figures['customer_total'] - (float) $this->shownTotal) >= 0.01) {
                $this->confirmed = $figures;

                return $this->notice = __('store.totals_changed');
            }

            DB::transaction(function () use ($order, $figures) {
                $order->fill($figures)->save();
                $order->items()->saveMany($order->items);
            });

            RateLimiter::hit($phoneKey, 3600);
            RateLimiter::hit($ipKey, 3600);
            $cart->clear();
            session()->push('placed_orders', $order->reference);

            return $this->redirect(url("/orders/{$order->reference}/done"));
        } finally {
            $lock->release();
        }
    }

    /** An unsaved order built from the cart at current prices. */
    private function draft($lines, Town $town): Order
    {
        $order = new Order([
            'reference' => Order::newReference(),
            'status' => OrderStatus::PendingReview,
            'town_id' => $town->id,
            'town_name' => $town->name,
            'district_name' => $town->district_name,
        ]);

        return $order->setRelation('items', $lines->values()->map(fn (array $line) => new OrderItem([
            'product_id' => $line['product']->id,
            'combination_id' => $line['combination_id'],
            'name' => $line['product']->name,
            'options_label' => $line['options'],
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'unit_cost' => $line['product']->cost_price,
        ])));
    }

    public function render(Cart $cart, Checkout $checkout)
    {
        $lines = $cart->lines()->filter(fn ($l) => $l['available']);
        $town = $this->town_id !== '' ? Town::where('available', true)->find($this->town_id) : null;

        $figures = $this->confirmed;

        if (! $figures && $town && $lines->isNotEmpty()) {
            $figures = $checkout->localFigures($this->draft($lines, $town), $town, $checkout->freeShippingAllowed());
        }

        $this->shownTotal = $figures['customer_total'] ?? null;

        return view('livewire.checkout-page', [
            'lines' => $lines,
            'subtotal' => Cart::total($lines),
            'figures' => $figures,
            'districts' => Town::where('available', true)->orderBy('district_id')->get(['district_id', 'district_name'])->unique('district_id'),
            'towns' => $this->district_id !== ''
                ? Town::where('available', true)->where('district_id', $this->district_id)->orderBy('name')->get(['id', 'name'])
                : collect(),
        ])->title(__('store.checkout'));
    }
}
