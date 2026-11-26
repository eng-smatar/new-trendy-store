<div class="wrap py-8 md:py-12">
    <h1 class="title-1 mb-8">{{ __('store.cart') }}</h1>

    @if ($lines->isEmpty())
        <div class="py-24 text-center">
            <p class="mb-6 text-lg">{{ __('store.cart_empty') }}</p>
            <a href="{{ url('/search') }}" class="btn-primary">{{ __('store.browse_all') }}</a>
        </div>
    @else
        <div class="grid grid-cols-[minmax(0,1fr)] gap-8 lg:grid-cols-[minmax(0,1fr)_22rem] lg:gap-12">
        <ul class="border-t border-line" wire:loading.class="opacity-50">
            @foreach ($lines as $line)
                <li wire:key="{{ $line['key'] }}" class="flex gap-4 border-b border-line py-4">
                    @if ($line['product']?->cover_image)
                        <img src="{{ $line['product']->cover_image }}" alt="" class="size-24 shrink-0 rounded-lg border border-line object-contain md:size-32">
                    @endif
                    <div class="min-w-0 flex-1 space-y-1">
                        <p class="line-clamp-2 text-right font-medium" dir="auto">{{ $line['product']?->name ?? __('store.product_gone') }}</p>
                        @if ($line['options'])<p class="text-sm text-muted">{{ $line['options'] }}</p>@endif
                        <x-price :amount="$line['unit_price'] * $line['quantity']" class="title-3 block" />
                        @unless ($line['available'])
                            <p class="error" role="alert">{{ __('store.line_unavailable') }}</p>
                        @endunless
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="flex items-center rounded-xl border border-ink/25">
                                <button type="button" class="grid size-11 place-items-center text-xl disabled:opacity-30" aria-label="{{ __('store.decrease') }}"
                                        wire:click="setQuantity('{{ $line['key'] }}', {{ $line['quantity'] - 1 }})" @disabled($line['quantity'] <= 1)>−</button>
                                <label class="sr-only" for="qty-{{ $line['key'] }}">{{ __('store.quantity') }}</label>
                                {{-- wire:key: a fresh input shows the new quantity after the buttons are used. --}}
                                <input wire:key="qty-{{ $line['key'] }}-{{ $line['quantity'] }}" id="qty-{{ $line['key'] }}" type="number" min="1" max="99" value="{{ $line['quantity'] }}"
                                       x-on:change="$el.value === '' ? $el.value = {{ $line['quantity'] }} : $wire.setQuantity('{{ $line['key'] }}', $el.value)" class="h-11 w-12 [appearance:textfield] bg-transparent text-center [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" class="grid size-11 place-items-center text-xl disabled:opacity-30" aria-label="{{ __('store.increase') }}"
                                        wire:click="setQuantity('{{ $line['key'] }}', {{ $line['quantity'] + 1 }})" @disabled($line['quantity'] >= 99)>+</button>
                            </div>
                            <button type="button" wire:click="remove('{{ $line['key'] }}')" class="btn-ghost">{{ __('store.remove') }}</button>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>

        <aside class="self-start rounded-xl bg-soft p-6">
        <div class="flex items-center justify-between gap-4">
            <span>{{ __('store.subtotal') }}</span>
            <x-price :amount="$subtotal" class="title-2" />
        </div>
        <p class="mt-2 text-sm text-muted">{{ __('store.delivery_at_checkout') }}</p>

        @if ($blocked)
            <p class="error mt-3" role="alert">{{ __('store.fix_cart') }}</p>
        @else
            <a href="{{ url('/checkout') }}" class="btn-primary mt-4 w-full">{{ __('store.checkout') }}</a>
        @endif
        </aside>
        </div>
    @endif
</div>
