@use(App\Models\Setting)
<x-layouts.store :title="__('store.order_received')">
    <div class="wrap grid gap-8 py-12 md:py-24 lg:grid-cols-2 lg:gap-16">
        <div>
            <h1 class="title-1">{{ __('store.order_received') }}</h1>
            <p class="mt-3 text-lg text-muted">{{ __('store.review_note') }}</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ url('/track') }}" class="btn-primary">{{ __('store.track') }}</a>
                <a href="{{ url('/') }}" class="btn-ghost">{{ __('store.back_home') }}</a>
            </div>
        </div>
        <dl class="self-start border-t border-line">
            <div class="flex justify-between gap-4 border-b border-line py-4"><dt>{{ __('store.reference') }}</dt><dd><bdi dir="ltr" class="title-3">{{ $order->reference }}</bdi></dd></div>
            <div class="flex justify-between gap-4 border-b border-line py-4"><dt>{{ __('store.total') }}</dt><dd><x-price :amount="$order->customer_total" class="title-3" /></dd></div>
            <div class="flex justify-between gap-4 border-b border-line py-4"><dt>{{ __('store.delivery') }}</dt><dd>{{ Setting::get('delivery_estimate') }}</dd></div>
        </dl>
    </div>
</x-layouts.store>
