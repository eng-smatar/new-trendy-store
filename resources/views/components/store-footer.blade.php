@use(App\Models\Category)
@use(App\Models\Setting)
@use(App\Models\Town)
@php
    $storeName = Setting::get('store_name');
    $categories = Category::listed()->whereIn('name', config('store.footer_categories'))->get()
        ->sortBy(fn ($c) => array_search($c->name, config('store.footer_categories')));

    // The fees Mart really charges today, per zone, with the admin's free-delivery thresholds.
    $zones = Town::where('available', true)->select('district_id', 'fees')->distinct()->get()
        ->groupBy(fn (Town $town) => $town->zone())
        ->map(fn ($towns) => $towns->flatMap(fn (Town $town) => array_column((array) $town->fees, 'fee'))->unique()->sort()->values())
        ->filter->isNotEmpty();
    $freeFrom = Setting::get('free_from');

    $icons = [
        'instagram' => '<path d="M12 2.2c3.2 0 3.6 0 4.9.1 3.3.1 4.8 1.7 4.9 4.9.1 1.3.1 1.6.1 4.8s0 3.6-.1 4.8c-.1 3.2-1.7 4.8-4.9 4.9-1.3.1-1.6.1-4.9.1s-3.6 0-4.8-.1c-3.3-.1-4.8-1.7-4.9-4.9-.1-1.3-.1-1.6-.1-4.8s0-3.6.1-4.8C2.4 4 4 2.4 7.2 2.3c1.2-.1 1.6-.1 4.8-.1zm0 4.6a5.2 5.2 0 1 0 0 10.4 5.2 5.2 0 0 0 0-10.4zm0 8.6a3.4 3.4 0 1 1 0-6.8 3.4 3.4 0 0 1 0 6.8zm5.4-10a1.2 1.2 0 1 0 0 2.4 1.2 1.2 0 0 0 0-2.4z"/>',
        'facebook' => '<path d="M13.5 22v-8h2.7l.4-3.2h-3.1v-2c0-.9.3-1.6 1.6-1.6h1.7V4.3c-.3 0-1.3-.1-2.5-.1-2.4 0-4.1 1.5-4.1 4.2v2.4H7.5V14h2.7v8z"/>',
        'tiktok' => '<path d="M16.6 2h-3.2v13.6a2.9 2.9 0 1 1-2-2.8V9.5a6.2 6.2 0 1 0 5.3 6.1V8.7a7.8 7.8 0 0 0 4.5 1.4V6.9a4.6 4.6 0 0 1-4.6-4.9z"/>',
    ];
@endphp
<footer class="bg-brand-deep text-white">
    <div class="wrap py-16">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-4 lg:col-span-2">
                <a href="{{ url('/') }}" class="inline-block rounded-lg bg-white p-2">
                    @if ($logo = Setting::logoUrl())<img src="{{ $logo }}" alt="{{ $storeName }}" class="h-11" loading="lazy">@else<span class="title-3 text-brand">{{ $storeName }}</span>@endif
                </a>
                @if ($about = Setting::get('footer_text'))<p class="max-w-sm leading-relaxed">{{ $about }}</p>@endif

                <ul class="flex flex-wrap items-center gap-3">
                    @foreach ((array) Setting::get('social_links') as $social)
                        @php $icon = collect($icons)->first(fn ($path, $name) => str_contains((string) parse_url($social['url'], PHP_URL_HOST), $name)); @endphp
                        <li>
                            <a href="{{ $social['url'] }}" rel="noopener" target="_blank"
                               class="{{ $icon ? 'size-11' : 'h-11 px-4' }} inline-flex items-center justify-center rounded-full border border-white/35 hover:border-white hover:bg-white hover:text-brand"
                               @if ($icon) aria-label="{{ $social['label'] }}" @endif>
                                @if ($icon)<svg viewBox="0 0 24 24" fill="currentColor" class="size-5" aria-hidden="true">{!! $icon !!}</svg>@else{{ $social['label'] }}@endif
                            </a>
                        </li>
                    @endforeach
                </ul>

                <ul class="flex flex-wrap gap-x-4 gap-y-2">
                    @if ($phone = Setting::get('contact_phone'))<li><a href="tel:{{ $phone }}"><bdi dir="ltr">{{ $phone }}</bdi></a></li>@endif
                    @if ($whatsapp = Setting::get('whatsapp'))<li><a href="https://wa.me/{{ $whatsapp }}" rel="noopener">{{ __('store.whatsapp') }}</a></li>@endif
                    @if ($email = Setting::get('contact_email'))<li><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                    @if ($address = Setting::get('contact_address'))<li>{{ $address }}</li>@endif
                </ul>
            </div>

            <nav aria-label="{{ __('store.shop_heading') }}">
                <h3 class="title-3">{{ __('store.shop_heading') }}</h3>
                <ul class="mt-2">
                    <li><a href="{{ url('/search') }}" class="inline-flex min-h-11 items-center underline-offset-8 hover:underline md:min-h-9">{{ __('store.all_products') }}</a></li>
                    <li><a href="{{ url('/') }}#categories" class="inline-flex min-h-11 items-center underline-offset-8 hover:underline md:min-h-9">{{ __('store.categories') }}</a></li>
                    @foreach ($categories as $category)
                        <li><a href="{{ url('/categories/'.$category->id) }}" class="inline-flex min-h-11 items-center underline-offset-8 hover:underline md:min-h-9">{{ $category->label() }}</a></li>
                    @endforeach
                </ul>
            </nav>

            <div>
                <h3 class="title-3">{{ __('store.shipping_heading') }}</h3>
                <ul class="mt-2">
                    @foreach (['Isreal', 'Jerusalem', 'default'] as $zone)
                        @if ($zones->has($zone))
                            <li class="py-1">
                                {{ __("store.zones.$zone") }}:
                                {{-- Words, not a dash: "20 – 30" reads backwards in right-to-left text. --}}
                                {{ $zones[$zone]->count() > 1
                                    ? __('store.fee_range', ['min' => number_format($zones[$zone]->first()), 'max' => number_format($zones[$zone]->last())])
                                    : '₪'.number_format($zones[$zone]->first()) }}@isset($freeFrom[$zone])، {{ __('store.free_from', ['amount' => number_format((float) $freeFrom[$zone])]) }}@endisset
                            </li>
                        @endif
                    @endforeach
                    <li class="py-1">{{ Setting::get('delivery_estimate') }}</li>
                </ul>
            </div>
        </div>

        <nav class="mt-10 flex flex-wrap gap-x-6" aria-label="{{ __('store.help_nav') }}">
            <a href="{{ url('/track') }}" class="inline-flex min-h-11 items-center underline underline-offset-4">{{ __('store.track') }}</a>
            @foreach (['about', 'delivery', 'privacy', 'terms'] as $page)
                <a href="{{ url('/pages/'.$page) }}" class="inline-flex min-h-11 items-center underline underline-offset-4">{{ __('store.page.'.$page) }}</a>
            @endforeach
        </nav>

        <div class="mt-6 flex flex-col items-center justify-between gap-2 border-t border-white/25 pt-6 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ $storeName }}. {{ __('store.rights') }}</p>
            <p>{{ __('store.cod_only') }}</p>
        </div>
    </div>
</footer>
