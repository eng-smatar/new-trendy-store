<?php

namespace App\Livewire;

use App\Models\Product;
use App\Services\Cart;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.store')]
class ProductPage extends Component
{
    #[Locked]
    public int $productId;

    public ?int $combinationId = null;

    public int $quantity = 1;

    public string $message = '';

    public function mount(int $id): void
    {
        $product = Product::visible()->findOrFail($id);
        $this->productId = $product->id;
        $this->combinationId = collect($product->combinations)->firstWhere('available_quantity', '>', 0)['id']
            ?? $product->combinations[0]['id'] ?? null;
    }

    public function addToCart(Cart $cart): void
    {
        $product = Product::visible()->findOrFail($this->productId);
        $inCart = $cart->raw()[$product->id.':'.$this->combinationId] ?? 0;
        $quantity = max(1, $this->quantity);

        if ($product->stock($this->combinationId) < $inCart + $quantity) {
            $this->message = __('store.not_enough_stock');

            return;
        }

        $cart->add($product->id, $this->combinationId, $quantity);
        $this->message = __('store.added');
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        $product = Product::visible()->findOrFail($this->productId);
        $images = $product->combination($this->combinationId)['images'] ?? [];

        return view('livewire.product-page', [
            'product' => $product,
            'images' => array_values(array_filter($images ?: ($product->images ?: [$product->cover_image]))),
            'stock' => $product->stock($this->combinationId),
            'related' => $product->category_id
                ? Product::visible()->where('category_id', $product->category_id)->where('id', '!=', $product->id)
                    ->where('total_quantity', '>', 0)->orderByDesc('id')->limit(4)->get()
                : collect(),
        ])->title($product->name);
    }
}
