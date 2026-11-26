@props(['product'])
{{-- An open tile: the photo in its frame, then name and price. No box around the words. --}}
<a href="{{ url('/products/'.$product->id) }}" class="lift flex h-full flex-col">
    <span class="block aspect-square overflow-hidden rounded-xl border border-line bg-white">
        @if ($product->cover_image)
            <img src="{{ $product->cover_image }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-contain p-2">
        @endif
    </span>
    <h3 class="mt-3 line-clamp-2 text-right leading-snug" dir="auto">{{ $product->name }}</h3>
    <span class="mt-auto flex items-center justify-between gap-2 pt-2">
        <x-price :amount="$product->selling_price" class="title-3" />
        @if ($product->total_quantity < 1)
            <span class="rounded-md bg-soft px-2 py-0.5 text-sm text-muted">{{ __('store.out_of_stock') }}</span>
        @endif
    </span>
</a>
