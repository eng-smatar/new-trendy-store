@props(['products'])
<ul class="grid grid-cols-2 gap-x-3 gap-y-8 md:grid-cols-3 md:gap-x-6 md:gap-y-12 lg:grid-cols-4">
    @foreach ($products as $product)
        <li wire:key="p{{ $product->id }}"><x-product-card :product="$product" /></li>
    @endforeach
</ul>
