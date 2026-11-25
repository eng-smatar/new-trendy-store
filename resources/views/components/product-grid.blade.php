@props(['products'])
<ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4">
    @foreach ($products as $product)
        <li wire:key="p{{ $product->id }}" class="reveal"><x-product-card :product="$product" /></li>
    @endforeach
</ul>
