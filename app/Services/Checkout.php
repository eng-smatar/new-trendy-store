<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Town;
use App\Services\Mart\MartClient;
use App\Services\Mart\MartException;
use Illuminate\Support\Facades\Cache;

/** Prices an order. $order may be unsaved; it must have its `items` relation set. */
class Checkout
{
    public function __construct(private MartClient $mart) {}

    /** Figures from local data only. Null when the town cannot be priced. */
    public function localFigures(Order $order, Town $town, bool $freeAllowed): ?array
    {
        $productsTotal = round($order->items->sum(fn ($i) => $i->unit_price * $i->quantity), 2);
        $margin = round($order->items->sum(fn ($i) => ($i->unit_price - $i->unit_cost) * $i->quantity), 2);
        $fee = $town->fee($productsTotal);

        if (! $town->available || $fee === null) {
            return null;
        }

        $threshold = Setting::get('free_from')[$town->zone()] ?? null;
        $free = $freeAllowed && $threshold !== null && $productsTotal >= (float) $threshold && $margin >= $fee;

        return [
            'products_total' => $productsTotal,
            'shipping_fee' => $fee,
            'free_shipping' => $free,
            'customer_total' => round($productsTotal + ($free ? 0 : $fee), 2),
            'expected_net' => round($margin - ($free ? $fee : 0), 2),
            'quote_verified' => false,
        ];
    }

    public function isStale(MartException $e): bool
    {
        return in_array($e->errorCode, ['price_below_minimum', 'insufficient_stock', 'product_not_orderable', 'product_not_offered', 'unknown_combination'], true);
    }

    /** Current cost, status and stock for a few products. False when Mart could not be read. */
    public function refreshProducts(array $ids): bool
    {
        $ids = array_slice(array_values(array_unique(array_map('intval', $ids))), 0, 100); // Mart's limit

        try {
            $fresh = collect($this->mart->get('products', ['product_ids' => implode(',', $ids), 'limit' => 100], 15)['data']['products'])->keyBy('product_id');
        } catch (MartException) {
            return false;
        }

        foreach (Product::findMany($ids) as $product) {
            $row = $fresh->get($product->id);

            // Saving recalculates the selling price from the new cost.
            $product->fill($row ? [
                'cost_price' => $row['price']['group_price'],
                'mart_active' => $row['status'] === 'active',
                'total_quantity' => $row['in_stock'] ? (int) $row['total_quantity'] : 0,
                'combinations' => $row['combinations'] ?? [],
            ] : ['removed_at' => now()])->save();
        }

        return true;
    }

    /** Whether this Mart account may absorb delivery fees. Unknown means no. */
    public function freeShippingAllowed(): bool
    {
        if (($cached = Cache::get('mart.free_shipping_allowed')) !== null) {
            return $cached;
        }

        try {
            $allowed = (bool) ($this->mart->get('account', [], 10)['data']['account']['free_shipping_allowed'] ?? false);
        } catch (MartException) {
            return false;
        }

        Cache::put('mart.free_shipping_allowed', $allowed, now()->addHour());

        return $allowed;
    }

    public function body(Order $order, bool $freeShipping): array
    {
        return [
            'payment_type' => 'cod',
            'partner_reference' => $order->reference,
            'shipping_address' => array_filter([
                'customer_name' => trim($order->first_name.' '.$order->last_name),
                'phone' => $order->phone,
                'state_id' => (int) $order->town_id,
                'address' => $order->address_line1,
                'notes' => $order->notes,
            ], fn ($v) => $v !== null && $v !== ''),
            'products' => $order->items->map(fn ($i) => array_filter([
                'product_id' => (int) $i->product_id,
                'combination_id' => $i->combination_id === null ? null : (int) $i->combination_id,
                'quantity' => (int) $i->quantity,
                'price' => round((float) $i->unit_price, 2),
            ], fn ($v) => $v !== null))->values()->all(),
            'free_shipping' => $freeShipping,
        ];
    }

    /**
     * Ask Mart what a create would freeze.
     *
     * @throws MartException
     */
    public function martFigures(Order $order, bool $freeShipping): array
    {
        $data = $this->mart->post('orders/validate', $this->body($order, $freeShipping), 20)['data'];
        $quote = $data['validation']['quote'] ?? $data['quote'] ?? null;

        if (! $quote) {
            throw new MartException(MartException::REFUSED, 'reference_already_used', 'Mart already has this order.');
        }

        return [
            'products_total' => (float) $quote['products_total'],
            'shipping_fee' => (float) $quote['shipping_fee'],
            'free_shipping' => (bool) $quote['free_shipping'],
            'customer_total' => (float) $quote['cod_total'],
            'expected_net' => (float) ($quote['reward']['net'] ?? 0),
            'quote_verified' => true,
        ];
    }

    /**
     * Figures to place an order with. Falls back to local figures when Mart cannot be reached.
     *
     * @throws CheckoutException
     */
    public function quote(Order $order, Town $town): array
    {
        $local = $this->localFigures($order, $town, $this->freeShippingAllowed())
            ?? throw new CheckoutException(__('store.town_unavailable'));

        try {
            try {
                return $this->martFigures($order, $local['free_shipping']);
            } catch (MartException $e) {
                if (! in_array($e->errorCode, ['free_shipping_not_covered', 'free_shipping_not_allowed'], true)) {
                    throw $e;
                }

                return $this->martFigures($order, false);
            }
        } catch (MartException $e) {
            if ($e->retryable()) {
                return $local;
            }

            report($e);

            // Our copy is behind Mart's: re-read these products so the next attempt shows the truth.
            if ($this->isStale($e) && $this->refreshProducts($order->items->pluck('product_id')->all())) {
                throw new CheckoutException(__('store.prices_updated'));
            }

            throw new CheckoutException(__('store.mart_refused'));
        }
    }
}
