<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/** Session cart: "productId:combinationId" => quantity. */
class Cart
{
    private const MAX = 99;

    public function add(int $productId, ?int $combinationId, int $quantity = 1): void
    {
        $key = $productId.':'.$combinationId;
        $this->set($key, ($this->raw()[$key] ?? 0) + max(1, $quantity));
    }

    public function set(string $key, int $quantity): void
    {
        $items = $this->raw();

        if ($quantity < 1) {
            unset($items[$key]);
        } else {
            $items[$key] = min($quantity, self::MAX);
        }

        session(['cart' => $items]);
    }

    public function clear(): void
    {
        session()->forget('cart');
    }

    public function raw(): array
    {
        return session('cart', []);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /** @return Collection<string, array> lines priced from the current catalogue */
    public function lines(): Collection
    {
        $items = $this->raw();
        $products = Product::findMany(array_map('intval', array_keys($items)))->keyBy('id');

        return collect($items)->map(function (int $quantity, string $key) use ($products) {
            [$productId, $combinationId] = explode(':', $key);
            $product = $products->get((int) $productId);
            $combinationId = $combinationId === '' ? null : (int) $combinationId;

            return [
                'key' => $key,
                'product' => $product,
                'combination_id' => $combinationId,
                'options' => $product?->optionLabel($combinationId),
                'quantity' => $quantity,
                'unit_price' => (float) ($product?->selling_price ?? 0),
                'available' => $product !== null && $product->isVisible() && $product->stock($combinationId) >= $quantity,
            ];
        });
    }

    public static function total(Collection $lines): float
    {
        return round($lines->sum(fn ($l) => $l['unit_price'] * $l['quantity']), 2);
    }
}
