@props(['product'])
<a href="{{ url('/products/'.$product->id) }}" class="card flex h-full flex-col overflow-hidden transition hover:shadow-md">
    <div class="aspect-square bg-stone-100">
        @if ($product->cover_image)
            <img src="{{ $product->cover_image }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-cover">
        @endif
    </div>
    <div class="flex flex-1 flex-col gap-2 p-3">
        <h3 class="line-clamp-2 text-sm" dir="auto">{{ $product->name }}</h3>
        <div class="mt-auto flex items-center justify-between gap-2">
            <x-price :amount="$product->selling_price" class="font-bold" />
            @if ($product->total_quantity < 1)
                <span class="rounded-full bg-stone-200 px-2 py-0.5 text-xs">{{ __('store.out_of_stock') }}</span>
            @endif
        </div>
    </div>
</a>
