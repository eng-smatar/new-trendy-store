@use(App\Models\Setting)
@php
    // Show what is common to every option once, and only what differs on the buttons.
    $options = collect($product->combinations);
    $pairs = fn (array $combination) => collect($combination['attributes'] ?? [])->mapWithKeys(fn ($a) => [($a['group'] ?? '') => $a['name'] ?? '']);
    $common = $options->count() > 1
        ? $options->skip(1)->reduce(fn ($carry, $c) => $carry->intersectAssoc($pairs($c)), $pairs($options->first()))
        : collect();
    $choice = function (array $combination) use ($pairs, $common, $product) {
        $differs = $pairs($combination)->diffAssoc($common);

        return $differs->isNotEmpty() ? $differs->values()->implode(' · ') : $product->optionLabel($combination['id']);
    };
    $groups = $options->flatMap(fn ($c) => $pairs($c)->diffAssoc($common)->keys())->filter()->unique()->implode(' / ');
@endphp
<div class="wrap py-6 md:py-10" x-data="{ atButton: true }">
    @if ($product->category)
        <a href="{{ url('/categories/'.$product->category->id) }}" class="link mb-2">{{ $product->category->label() }}</a>
    @endif

    <div class="grid gap-8 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:gap-16">
        <div wire:key="gallery-{{ $combinationId }}" x-data="{ current: 0 }">
            <div class="aspect-square overflow-hidden rounded-xl border border-line bg-white">
                @foreach ($images as $i => $image)
                    <img src="{{ $image }}" alt="{{ $product->name }}" class="size-full object-contain p-4"
                         x-show="current === {{ $i }}" @if ($i > 0) loading="lazy" x-cloak style="display:none" @endif>
                @endforeach
            </div>
            @if (count($images) > 1)
                <div class="mt-3 flex gap-3 overflow-x-auto p-1">
                    @foreach ($images as $i => $image)
                        <button type="button" x-on:click="current = {{ $i }}" x-bind:aria-pressed="current === {{ $i }}"
                                class="size-20 shrink-0 overflow-hidden rounded-lg border-2 bg-white" x-bind:class="current === {{ $i }} ? 'border-brand' : 'border-line'">
                            <img src="{{ $image }}" alt="{{ __('store.image_n', ['n' => $i + 1]) }}" loading="lazy" class="size-full object-contain">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="space-y-3">
                <h1 class="title-2 text-right" dir="auto">{{ $product->name }}</h1>
                <x-price :amount="$product->selling_price" class="title-1 block text-brand" />
            </div>

            @if ($product->combinations)
                <fieldset>
                    <legend class="label">{{ $groups ?: __('store.choose_option') }}</legend>
                    @if ($common->isNotEmpty())
                        <p class="mb-2 text-muted">{{ $common->map(fn ($v, $k) => trim("$k: $v", ': '))->implode('، ') }}</p>
                    @endif
                    <div class="flex flex-wrap gap-2">
                        @foreach ($product->combinations as $combination)
                            <label wire:key="c{{ $combination['id'] }}" class="chip min-w-14 cursor-pointer has-checked:border-brand has-checked:bg-brand-soft has-checked:font-bold has-focus-visible:outline-2 has-focus-visible:outline-brand {{ $combination['available_quantity'] < 1 ? 'text-muted line-through' : '' }}">
                                <input type="radio" wire:model.live.number="combinationId" value="{{ $combination['id'] }}" class="sr-only">
                                {{ $choice($combination) }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <p class="font-medium {{ $stock > 0 ? 'text-green-800' : 'text-red-700' }}" role="status">
                {{ $stock > 0 ? __('store.in_stock') : __('store.out_of_stock') }}
            </p>

            <div class="flex gap-3" x-intersect:enter="atButton = true" x-intersect:leave="atButton = false">
                <div class="flex items-center rounded-xl border border-ink/25">
                    <button type="button" class="grid size-11 place-items-center text-xl" aria-label="{{ __('store.decrease') }}"
                            x-on:click="$wire.quantity = Math.max(1, Number($wire.quantity) - 1)">−</button>
                    <label for="quantity" class="sr-only">{{ __('store.quantity') }}</label>
                    <input id="quantity" type="number" min="1" max="99" wire:model.number="quantity" class="h-11 w-11 [appearance:textfield] bg-transparent text-center [&::-webkit-inner-spin-button]:appearance-none">
                    <button type="button" class="grid size-11 place-items-center text-xl" aria-label="{{ __('store.increase') }}"
                            x-on:click="$wire.quantity = Math.min(99, Number($wire.quantity) + 1)">+</button>
                </div>
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary flex-1" @disabled($stock < 1)>
                    <span wire:loading wire:target="addToCart" class="size-4 rounded-full border-2 border-white/40 border-t-white motion-safe:animate-spin" aria-hidden="true"></span>
                    {{ __('store.add_to_cart') }}
                </button>
            </div>

            <p role="status" class="error">{{ $message }}</p>

            <ul class="border-t border-line">
                <li class="border-b border-line py-3">{{ __('store.cod') }}</li>
                <li class="border-b border-line py-3">{{ Setting::get('delivery_estimate') }}</li>
                <li class="border-b border-line py-3">{{ __('store.review_fact') }}</li>
            </ul>

            @if ($product->description_short)
                <div class="rich" dir="auto">{!! \App\Models\Product::safeHtml($product->description_short) !!}</div>
            @endif
        </div>
    </div>

    @if ($product->description)
        <section class="mt-16 grid gap-4 border-t border-line pt-10 md:mt-24 lg:grid-cols-[minmax(0,3fr)_minmax(0,9fr)] lg:gap-16">
            <h2 class="title-2">{{ __('store.description') }}</h2>
            <div class="rich max-w-3xl overflow-x-auto" dir="auto">{!! \App\Models\Product::safeHtml($product->description) !!}</div>
        </section>
    @endif

    @if ($related->isNotEmpty())
        <section class="mt-16 md:mt-24">
            <h2 class="title-2 mb-8">{{ __('store.related') }}</h2>
            <x-product-grid :products="$related" />
        </section>
    @endif

    {{-- Phones: keep buying one tap away once the main button has scrolled off. --}}
    @if ($stock > 0)
        <div class="sticky-buy fixed inset-x-0 bottom-0 z-30 flex items-center gap-3 border-t border-line bg-white p-3 md:hidden"
             x-show="! atButton" x-cloak x-transition.opacity style="display: none">
            <x-price :amount="$product->selling_price" class="title-3 text-brand" />
            <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary min-h-12 flex-1">{{ __('store.add_to_cart') }}</button>
        </div>
    @endif
</div>
