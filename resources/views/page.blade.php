<x-layouts.store :title="__('store.page.'.$page)">
    <article class="wrap grid gap-6 py-8 md:py-16 lg:grid-cols-[minmax(0,4fr)_minmax(0,8fr)] lg:gap-16">
        <h1 class="title-1">{{ __('store.page.'.$page) }}</h1>
        {{-- The editor saves an empty page as "<p></p>". --}}
        @if (trim(html_entity_decode(strip_tags((string) $body)), " \t\n\r\u{A0}") !== '')
            <div class="rich max-w-2xl text-lg">{!! str($body)->sanitizeHtml() !!}</div>
        @else
            <p class="text-lg text-muted">{{ __('store.page_soon') }}</p>
        @endif
    </article>
</x-layouts.store>
