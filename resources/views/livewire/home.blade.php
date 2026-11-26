@use(App\Models\Setting)
<div>
    @if ($announcement)
        <p class="wrap pt-4 text-sm font-medium">{{ $announcement }}</p>
    @endif

    {{-- Hero: what is sold, one hard fact, and the goods themselves. --}}
    <section class="wrap pt-8 pb-12 md:min-h-[calc(100svh-4.5rem)] md:pt-12 md:pb-16">
        <div class="enter mb-6 flex flex-col gap-4 md:mb-8 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="title-1">{{ __('store.hero_title') }}</h1>
                <p class="mt-2 max-w-xl text-lg text-muted">{{ __('store.hero_lead', ['estimate' => Setting::get('delivery_estimate')]) }}</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/search') }}" class="btn-primary">{{ __('store.browse_all') }}</a>
                <a href="{{ url('/track') }}" class="btn-ghost">{{ __('store.track') }}</a>
            </div>
        </div>
        @if ($slides->isNotEmpty())
            <div class="enter-late"><x-hero-slider :products="$slides" /></div>
        @endif
    </section>

    @if ($categories['tiles']->isNotEmpty())
        <section id="categories" class="scroll-mt-24 bg-soft py-16 md:py-24">
            <div class="wrap">
                <h2 class="title-2 mb-8">{{ __('store.shop_by_category') }}</h2>
                <ul class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-6">
                    @foreach ($categories['tiles'] as $tile)
                        <li>
                            <a href="{{ url('/categories/'.$tile['category']->id) }}" class="lift block">
                                <span class="block aspect-square overflow-hidden rounded-xl bg-white">
                                    <img src="{{ $tile['image'] }}" alt="" loading="lazy" class="size-full object-contain p-4">
                                </span>
                                <span class="mt-3 flex items-baseline justify-between gap-2">
                                    <span class="title-3" dir="auto">{{ $tile['category']->label() }}</span>
                                    <span class="shrink-0 text-sm text-muted">{{ number_format($tile['products']) }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if ($categories['more']->isNotEmpty())
                    <ul class="mt-8 flex flex-wrap gap-2" aria-label="{{ __('store.more_categories') }}">
                        @foreach ($categories['more'] as $category)
                            <li><a href="{{ url('/categories/'.$category->id) }}" class="chip" dir="auto">{{ $category->label() }}</a></li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif

    @foreach (['featured' => $featured, 'new_arrivals' => $arrivals] as $heading => $products)
        @if ($products->isNotEmpty())
            <section class="wrap py-16 md:py-24">
                <div class="mb-8 flex items-center justify-between gap-4">
                    <h2 class="title-2">{{ __("store.$heading") }}</h2>
                    <a href="{{ url('/search') }}" class="link">{{ __('store.see_all') }}</a>
                </div>
                <x-product-grid :products="$products" />
            </section>
        @endif
    @endforeach

    @if ($featured->isEmpty() && $arrivals->isEmpty())
        <p class="wrap py-16 text-center text-muted">{{ __('store.empty_store') }}</p>
    @else
        {{-- The one brand band: how an order actually works here. --}}
        <section class="bg-brand py-16 text-white md:py-24">
            <div class="wrap">
                <h2 class="title-2 mb-8">{{ __('store.how_title') }}</h2>
                <ol class="grid gap-8 md:grid-cols-3 md:gap-12">
                    @foreach (__('store.how_steps') as $i => $step)
                        <li class="border-t border-white/30 pt-4">
                            <span class="text-sm" aria-hidden="true">{{ $i + 1 }}</span>
                            <h3 class="title-3 mt-1">{{ $step['title'] }}</h3>
                            <p class="mt-2">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>
    @endif
</div>
