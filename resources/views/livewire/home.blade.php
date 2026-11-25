<div class="space-y-10">
    @if ($announcement)
        <p class="rounded-xl bg-brand-soft p-3 text-center font-medium">{{ $announcement }}</p>
    @endif

    @if ($banners)
        <div class="flex snap-x gap-3 overflow-x-auto">
            @foreach ($banners as $banner)
                <a @if (! empty($banner['link'])) href="{{ $banner['link'] }}" @endif class="w-full shrink-0 snap-center overflow-hidden rounded-2xl">
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($banner['image']) }}" alt="{{ $banner['alt'] }}" class="aspect-[5/2] w-full object-cover">
                </a>
            @endforeach
        </div>
    @endif
    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('store.categories') }}" class="flex gap-2 overflow-x-auto pb-2">
            @foreach ($categories as $category)
                <a href="{{ url('/categories/'.$category->id) }}" class="btn-ghost shrink-0" dir="auto">{{ $category->label() }}</a>
            @endforeach
        </nav>
    @endif

    @foreach (['featured' => $featured, 'new_arrivals' => $arrivals] as $heading => $products)
        @if ($products->isNotEmpty())
            <section>
                <h2 class="mb-4 text-xl font-bold">{{ __("store.$heading") }}</h2>
                <x-product-grid :products="$products" />
            </section>
        @endif
    @endforeach

    @if ($featured->isEmpty() && $arrivals->isEmpty())
        <p class="py-16 text-center text-stone-600">{{ __('store.empty_store') }}</p>
    @endif
</div>
