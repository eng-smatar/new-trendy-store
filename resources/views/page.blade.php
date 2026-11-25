<x-layouts.store :title="__('store.page.'.$page)">
    <article class="card mx-auto max-w-2xl p-5">
        <h1 class="mb-3 text-2xl font-bold">{{ __('store.page.'.$page) }}</h1>
        @if ($body)
            <div class="rich">{!! str($body)->sanitizeHtml() !!}</div>
        @else
            <p class="text-stone-600">{{ __('store.page_soon') }}</p>
        @endif
    </article>
</x-layouts.store>
