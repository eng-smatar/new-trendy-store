<div class="wrap py-8 md:py-12">
    <div class="flex flex-wrap items-baseline justify-between gap-x-6 gap-y-1">
        <h1 class="title-1" dir="auto">
            {{ $category ? $category->label() : ($q !== '' ? __('store.results_for', ['q' => $q]) : __('store.all_products')) }}
        </h1>
        <p class="text-muted" role="status">{{ __('store.count', ['count' => number_format($products->total())]) }}</p>
    </div>

    @if ($subcategories->isNotEmpty())
        <nav aria-label="{{ __('store.categories') }}" class="mt-6 flex flex-wrap gap-2">
            @foreach ($subcategories as $sub)
                <a href="{{ url('/categories/'.$sub->id) }}" class="chip" dir="auto">{{ $sub->label() }}</a>
            @endforeach
        </nav>
    @endif

    {{-- Filters stay folded on phones and lie open as one row from tablet up. --}}
    <div x-data="{ open: false }" class="mt-6 border-y border-line py-4">
        <button type="button" class="btn-ghost w-full md:hidden" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-expanded="false" aria-controls="filters">{{ __('store.filters') }}</button>
        <div id="filters" class="mt-4 gap-3 md:mt-0 md:!grid md:grid-cols-4 md:items-end md:gap-6" x-bind:class="open ? 'grid' : 'hidden'" wire:ignore.self>
            <div>
                <label for="sort" class="label">{{ __('store.sort') }}</label>
                <select id="sort" wire:model.live="sort" class="field">
                    <option value="newest">{{ __('store.sort_newest') }}</option>
                    <option value="price_asc">{{ __('store.sort_price_asc') }}</option>
                    <option value="price_desc">{{ __('store.sort_price_desc') }}</option>
                </select>
            </div>
            <div>
                <label for="min" class="label">{{ __('store.price_from') }}</label>
                <input id="min" type="number" min="0" inputmode="decimal" wire:model.live.debounce.500ms="min" class="field">
            </div>
            <div>
                <label for="max" class="label">{{ __('store.price_to') }}</label>
                <input id="max" type="number" min="0" inputmode="decimal" wire:model.live.debounce.500ms="max" class="field">
            </div>
            <label class="flex min-h-12 items-center gap-3">
                <input type="checkbox" wire:model.live="available" class="size-5 accent-brand">
                {{ __('store.available_only') }}
            </label>
        </div>
    </div>

    <div wire:loading.class="opacity-50" class="mt-8">
        @if ($products->isEmpty())
            <div class="py-24 text-center">
                <p class="mb-6 text-lg">{{ __('store.no_results') }}</p>
                <a href="{{ url('/search') }}" class="btn-primary">{{ __('store.browse_all') }}</a>
            </div>
        @else
            <h2 class="sr-only">{{ __('store.products_heading') }}</h2>
            <x-product-grid :products="$products" />
            <div class="mt-12">{{ $products->links('components.pager') }}</div>
        @endif
    </div>
</div>
