<?php

namespace App\Livewire;

use App\Services\Cart;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.store')]
class CartPage extends Component
{
    public function setQuantity(string $key, $quantity, Cart $cart): void
    {
        if (is_numeric($quantity) && array_key_exists($key, $cart->raw())) {
            $cart->set($key, (int) $quantity);
            $this->dispatch('cart-updated');
        }
    }

    public function remove(string $key, Cart $cart): void
    {
        $this->setQuantity($key, 0, $cart);
    }

    public function render(Cart $cart)
    {
        $lines = $cart->lines();

        return view('livewire.cart-page', [
            'lines' => $lines,
            'subtotal' => Cart::total($lines),
            'blocked' => $lines->contains('available', false),
        ])->title(__('store.cart'));
    }
}
