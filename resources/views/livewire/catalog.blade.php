<div>
    <h1 class="mb-3 text-2xl font-bold" dir="auto">
        {{ $category ? $category->label() : ($q !== '' ? __('store.results_for', ['q' => $q]) : __('store.all_products')) }}
    </h1>

    @if ($subcategories->isNotEmpty())
        <nav aria-label="{{ __('store.categories') }}" class="mb-3 flex gap-2 overflow-x-auto pb-2">
            @foreach ($subcategories as $sub)
                <a href="{{ url('/categories/'.$sub->id) }}" class="btn-ghost shrink-0" dir="auto">{{ $sub->label() }}</a>
            @endforeach
        </nav>
    @endif

    <details class="card mb-4 p-3" wire:ignore.self>
        <summary class="min-h-8 cursor-pointer font-bold">{{ __('store.filters') }}</summary>
        <div class="mt-3 grid gap-3 sm:grid-cols-4">
            <div>
                <label for="min" class="label">{{ __('store.price_from') }}</label>
                <input id="min" type="number" min="0" inputmode="decimal" wire:model.live.debounce.500ms="min" class="field">
            </div>
            <div>
                <label for="max" class="label">{{ __('store.price_to') }}</label>
                <input id="max" type="number" min="0" inputmode="decimal" wire:model.live.debounce.500ms="max" class="field">
            </div>
            <div>
                <label for="sort" class="label">{{ __('store.sort') }}</label>
                <select id="sort" wire:model.live="sort" class="field">
                    <option value="newest">{{ __('store.sort_newest') }}</option>
                    <option value="price_asc">{{ __('store.sort_price_asc') }}</option>
                    <option value="price_desc">{{ __('store.sort_price_desc') }}</option>
                </select>
            </div>
            <label class="flex min-h-11 items-center gap-2 self-end">
                <input type="checkbox" wire:model.live="available" class="size-5">
                {{ __('store.available_only') }}
            </label>
        </div>
    </details>

    <div wire:loading.class="opacity-50">
        @if ($products->isEmpty())
            <div class="py-16 text-center" role="status">
                <p class="mb-4 text-stone-600">{{ __('store.no_results') }}</p>
                <a href="{{ url('/') }}" class="btn-primary">{{ __('store.back_home') }}</a>
            </div>
        @else
            <p class="mb-3 text-sm text-stone-600" role="status">{{ __('store.count', ['count' => $products->total()]) }}</p>
            <x-product-grid :products="$products" />
            <div class="mt-6">{{ $products->links('components.pager') }}</div>
        @endif
    </div>
</div>
