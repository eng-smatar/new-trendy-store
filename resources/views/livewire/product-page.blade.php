<div class="space-y-10" x-data="{ atButton: true }">
    <div class="grid gap-6 md:grid-cols-2">
        <div wire:key="gallery-{{ $combinationId }}" x-data="{ current: 0 }">
            <div class="card aspect-square overflow-hidden bg-stone-100">
                @foreach ($images as $i => $image)
                    <img src="{{ $image }}" alt="{{ $product->name }}" class="size-full object-contain"
                         x-show="current === {{ $i }}" @if ($i > 0) loading="lazy" x-cloak style="display:none" @endif>
                @endforeach
            </div>
            @if (count($images) > 1)
                <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($images as $i => $image)
                        <button type="button" x-on:click="current = {{ $i }}" x-bind:aria-pressed="current === {{ $i }}"
                                class="size-16 shrink-0 overflow-hidden rounded-lg border-2" x-bind:class="current === {{ $i }} ? 'border-brand' : 'border-stone-200'">
                            <img src="{{ $image }}" alt="{{ __('store.image_n', ['n' => $i + 1]) }}" loading="lazy" class="size-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <h1 class="text-2xl font-bold" dir="auto">{{ $product->name }}</h1>
            <x-price :amount="$product->selling_price" class="block text-2xl font-bold text-brand" />

            @if ($product->combinations)
                <fieldset>
                    <legend class="label">{{ __('store.choose_option') }}</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->combinations as $combination)
                            <label wire:key="c{{ $combination['id'] }}" class="btn-ghost cursor-pointer has-checked:border-brand has-checked:bg-brand-soft has-focus-visible:outline-3 has-focus-visible:outline-brand {{ $combination['available_quantity'] < 1 ? 'text-stone-500 line-through' : '' }}">
                                <input type="radio" wire:model.live.number="combinationId" value="{{ $combination['id'] }}" class="sr-only">
                                {{ $product->optionLabel($combination['id']) }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <p class="text-sm {{ $stock > 0 ? 'text-green-700' : 'text-red-700' }}" role="status">
                {{ $stock > 0 ? __('store.in_stock') : __('store.out_of_stock') }}
            </p>

            <div class="flex gap-2" x-intersect:enter="atButton = true" x-intersect:leave="atButton = false">
                <div class="flex items-center rounded-xl border border-stone-300 bg-white">
                    <button type="button" class="grid size-11 place-items-center text-xl" aria-label="{{ __('store.decrease') }}"
                            x-on:click="$wire.quantity = Math.max(1, Number($wire.quantity) - 1)">−</button>
                    <label for="quantity" class="sr-only">{{ __('store.quantity') }}</label>
                    <input id="quantity" type="number" min="1" max="99" wire:model.number="quantity" class="h-11 w-12 [appearance:textfield] bg-transparent text-center [&::-webkit-inner-spin-button]:appearance-none">
                    <button type="button" class="grid size-11 place-items-center text-xl" aria-label="{{ __('store.increase') }}"
                            x-on:click="$wire.quantity = Math.min(99, Number($wire.quantity) + 1)">+</button>
                </div>
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary flex-1" @disabled($stock < 1)>
                    <span wire:loading wire:target="addToCart" class="size-4 rounded-full border-2 border-white/40 border-t-white motion-safe:animate-spin" aria-hidden="true"></span>
                    {{ __('store.add_to_cart') }}
                </button>
            </div>

            <p role="status" class="error">{{ $message }}</p>

            @if ($product->description_short)
                <div class="rich" dir="auto">{!! str($product->description_short)->sanitizeHtml() !!}</div>
            @endif
        </div>
    </div>

    @if ($product->description)
        <section class="card overflow-x-auto p-4">
            <h2 class="mb-2 text-xl font-bold">{{ __('store.description') }}</h2>
            <div class="rich" dir="auto">{!! str($product->description)->sanitizeHtml() !!}</div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section>
            <h2 class="mb-4 text-xl font-bold">{{ __('store.related') }}</h2>
            <x-product-grid :products="$related" />
        </section>
    @endif

    {{-- Phones: keep buying one tap away once the main button has scrolled off. --}}
    @if ($stock > 0)
        <div class="sticky-buy fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-stone-200 bg-white p-3 shadow-2xl md:hidden"
             x-show="! atButton" x-cloak x-transition.opacity style="display: none">
            <x-price :amount="$product->selling_price" class="text-lg font-bold text-brand" />
            <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary flex-1">{{ __('store.add_to_cart') }}</button>
        </div>
    @endif
</div>
