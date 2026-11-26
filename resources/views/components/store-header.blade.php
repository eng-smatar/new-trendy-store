@use(App\Models\Category)
@use(App\Models\Setting)
@php
    $storeName = Setting::get('store_name');
    $logo = Setting::logoUrl();
    $categories = Category::listed()->whereIn('name', config('store.footer_categories'))->get()
        ->sortBy(fn ($c) => array_search($c->name, config('store.footer_categories')));
    $current = fn (string $path) => request()->is($path) ? 'page' : null;
@endphp
<header class="sticky top-0 z-40 border-b border-line bg-white">
    <div class="wrap flex flex-wrap items-center gap-x-8 gap-y-2 py-2 md:min-h-18 md:flex-nowrap">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-6 focus:top-3 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3">{{ __('store.skip') }}</a>

        <a href="{{ url('/') }}" class="flex min-h-11 shrink-0 items-center" aria-label="{{ $storeName }}">
            @if ($logo)<img src="{{ $logo }}" alt="{{ $storeName }}" class="h-10 max-w-40 object-contain md:h-12">@else<span class="title-3">{{ $storeName }}</span>@endif
        </a>

        <nav aria-label="{{ __('store.main_nav') }}" class="hidden items-center gap-6 text-[15px] font-medium lg:flex">
            <a href="{{ url('/search') }}" class="link text-ink aria-[current]:border-b-2 aria-[current]:border-brand" @if ($current('search') && ! request('q')) aria-current="page" @endif>{{ __('store.all_products') }}</a>
            @foreach ($categories as $category)
                <a href="{{ url('/categories/'.$category->id) }}" class="link text-ink aria-[current]:border-b-2 aria-[current]:border-brand" @if ($current('categories/'.$category->id)) aria-current="page" @endif>{{ $category->label() }}</a>
            @endforeach
        </nav>

        <form action="{{ url('/search') }}" role="search" class="order-last flex w-full gap-2 md:order-none md:ms-auto md:w-80">
            <label for="q" class="sr-only">{{ __('store.search') }}</label>
            <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('store.search_placeholder') }}" class="field min-h-11">
            <button class="btn-ghost shrink-0 px-4" aria-label="{{ __('store.search') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            </button>
        </form>

        <div class="ms-auto md:ms-0"><livewire:cart-badge /></div>
    </div>
</header>
