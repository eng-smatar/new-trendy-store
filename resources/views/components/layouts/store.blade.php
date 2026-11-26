@php
    use App\Models\Setting;
    $storeName = Setting::get('store_name');
    $upload = fn (?string $path) => $path ? \Illuminate\Support\Facades\Storage::disk('public')->url($path) : null;
    $description = Setting::get('seo_description') ?: Setting::get('footer_text');
    $shareImage = $upload(Setting::get('seo_image')) ?? Setting::logoUrl();
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', (string) Setting::get('main_color')) ? Setting::get('main_color') : '#3f2878';
    $pageTitle = isset($title) ? $title.' | '.$storeName : (Setting::get('seo_title') ?: $storeName.': '.__('store.title_line'));
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:type" content="website">
    @if ($shareImage)<meta property="og:image" content="{{ $shareImage }}"><meta name="twitter:card" content="summary_large_image">@endif
    <meta name="theme-color" content="#ffffff">
    <link rel="icon" href="{{ $upload(Setting::get('icon')) ?? 'data:,' }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-white font-sans text-ink antialiased" style="--brand: {{ $color }}">
    <x-store-header />

    <main id="main" class="flex-1">
        {{ $slot }}
    </main>

    <x-store-footer />
    <x-toast />
</body>
</html>
