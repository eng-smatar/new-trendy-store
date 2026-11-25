<?php

use App\Livewire\CartPage;
use App\Livewire\Catalog;
use App\Livewire\CheckoutPage;
use App\Livewire\Home;
use App\Livewire\ProductPage;
use App\Livewire\TrackOrder;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;

Route::livewire('/', Home::class);
Route::livewire('/search', Catalog::class);
Route::livewire('/categories/{id}', Catalog::class)->whereNumber('id');
Route::livewire('/products/{id}', ProductPage::class)->whereNumber('id');
Route::livewire('/cart', CartPage::class);
Route::livewire('/checkout', CheckoutPage::class);
Route::livewire('/track', TrackOrder::class);

Route::get('/pages/{page}', fn (string $page) => view('page', ['page' => $page, 'body' => Setting::get("page_$page")]))
    ->whereIn('page', ['about', 'delivery', 'privacy', 'terms']);

Route::get('/orders/{reference}/done', function (string $reference) {
    abort_unless(in_array($reference, session('placed_orders', []), true), 404);

    return view('order-done', ['order' => Order::where('reference', $reference)->firstOrFail()]);
});
