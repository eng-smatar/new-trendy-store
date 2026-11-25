@php
    use App\Models\Setting;
    $storeName = Setting::get('store_name');
    $upload = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    $logo = Setting::logoUrl();
    $description = Setting::get('seo_description');
    $shareImage = $upload(Setting::get('seo_image'));
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) Setting::get('main_color')) ? Setting::get('main_color') : '#3f2878';
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title.' | '.$storeName : (Setting::get('seo_title') ?: $storeName) }}</title>
    @if ($description)<meta name="description" content="{{ $description }}"><meta property="og:description" content="{{ $description }}">@endif
    @if ($shareImage)<meta property="og:image" content="{{ $shareImage }}">@endif
    <link rel="icon" href="{{ $upload(Setting::get('icon')) ?? 'data:,' }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-stone-50 font-sans text-stone-900" style="--brand: {{ $color }}">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:p-3">{{ __('store.skip') }}</a>

    <header class="sticky top-0 z-40 border-b border-stone-200 bg-white">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-3 px-4 py-3">
            <a href="{{ url('/') }}" class="text-lg font-bold text-brand">
                @if ($logo)<img src="{{ $logo }}" alt="{{ $storeName }}" class="h-11 max-w-44 object-contain">@else{{ $storeName }}@endif
            </a>
            <livewire:cart-badge />
            <form action="{{ url('/search') }}" role="search" class="order-last flex w-full gap-2 sm:order-none sm:mx-auto sm:w-96">
                <label for="q" class="sr-only">{{ __('store.search') }}</label>
                <input id="q" name="q" type="search" value="{{ request('q') }}" placeholder="{{ __('store.search_placeholder') }}" class="field">
                <button class="btn-primary">{{ __('store.search') }}</button>
            </form>
        </div>
    </header>

    <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 py-6">
        {{ $slot }}
    </main>

    <footer class="border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-stone-600">
            <p>{{ __('store.cod_note') }}</p>
            <nav class="mt-3 flex flex-wrap gap-x-4 gap-y-2">
                <a href="{{ url('/track') }}" class="underline">{{ __('store.track') }}</a>
                @foreach (['about', 'delivery', 'privacy', 'terms'] as $page)
                    <a href="{{ url('/pages/'.$page) }}" class="underline">{{ __('store.page.'.$page) }}</a>
                @endforeach
            </nav>
            <ul class="mt-3 flex flex-wrap gap-x-4 gap-y-2">
                @if ($phone = Setting::get('contact_phone'))<li><a href="tel:{{ $phone }}"><bdi dir="ltr">{{ $phone }}</bdi></a></li>@endif
                @if ($whatsapp = Setting::get('whatsapp'))<li><a href="https://wa.me/{{ $whatsapp }}" rel="noopener">{{ __('store.whatsapp') }}</a></li>@endif
                @if ($email = Setting::get('contact_email'))<li><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if ($address = Setting::get('contact_address'))<li>{{ $address }}</li>@endif
                @foreach ((array) Setting::get('social_links') as $social)
                    <li><a href="{{ $social['url'] }}" rel="noopener" class="underline">{{ $social['label'] }}</a></li>
                @endforeach
            </ul>
            <p class="mt-2">&copy; {{ date('Y') }} {{ $storeName }}</p>
        </div>
    </footer>
</body>
</html>
