<div class="mx-auto max-w-2xl">
    <h1 class="mb-4 text-2xl font-bold">{{ __('store.cart') }}</h1>

    @if ($lines->isEmpty())
        <div class="py-16 text-center">
            <p class="mb-4 text-stone-600">{{ __('store.cart_empty') }}</p>
            <a href="{{ url('/') }}" class="btn-primary">{{ __('store.back_home') }}</a>
        </div>
    @else
        <ul class="space-y-3" wire:loading.class="opacity-50">
            @foreach ($lines as $line)
                <li wire:key="{{ $line['key'] }}" class="card flex gap-3 p-3">
                    @if ($line['product']?->cover_image)
                        <img src="{{ $line['product']->cover_image }}" alt="" class="size-20 rounded-lg object-cover">
                    @endif
                    <div class="min-w-0 flex-1 space-y-1">
                        <p class="truncate font-medium" dir="auto">{{ $line['product']?->name ?? __('store.product_gone') }}</p>
                        @if ($line['options'])<p class="text-sm text-stone-600">{{ $line['options'] }}</p>@endif
                        <x-price :amount="$line['unit_price'] * $line['quantity']" class="font-bold" />
                        @unless ($line['available'])
                            <p class="error" role="alert">{{ __('store.line_unavailable') }}</p>
                        @endunless
                        <div class="flex items-center gap-2">
                            <label class="sr-only" for="qty-{{ $line['key'] }}">{{ __('store.quantity') }}</label>
                            <input id="qty-{{ $line['key'] }}" type="number" min="1" max="99" value="{{ $line['quantity'] }}"
                                   wire:change="setQuantity('{{ $line['key'] }}', $event.target.value)" class="field w-24">
                            <button type="button" wire:click="remove('{{ $line['key'] }}')" class="btn-ghost">{{ __('store.remove') }}</button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="card mt-4 flex items-center justify-between p-4">
            <span>{{ __('store.subtotal') }}</span>
            <x-price :amount="$subtotal" class="text-lg font-bold" />
        </div>

        @if ($blocked)
            <p class="error mt-3" role="alert">{{ __('store.fix_cart') }}</p>
        @else
            <a href="{{ url('/checkout') }}" class="btn-primary mt-4 w-full">{{ __('store.checkout') }}</a>
        @endif
    @endif
</div>
