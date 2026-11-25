<div class="space-y-10">
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

            <div class="flex gap-2">
                <label for="quantity" class="sr-only">{{ __('store.quantity') }}</label>
                <input id="quantity" type="number" min="1" max="99" wire:model.number="quantity" class="field w-24">
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" class="btn-primary flex-1" @disabled($stock < 1)>{{ __('store.add_to_cart') }}</button>
            </div>

            <p role="status" class="text-sm">
                @if ($message)
                    {{ $message }} <a href="{{ url('/cart') }}" class="font-bold text-brand underline">{{ __('store.view_cart') }}</a>
                @endif
            </p>

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
</div>
