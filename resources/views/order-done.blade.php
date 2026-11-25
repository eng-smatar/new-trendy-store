<x-layouts.store :title="__('store.order_received')">
    <div class="card mx-auto max-w-xl space-y-3 p-6 text-center">
        <h1 class="text-2xl font-bold text-brand">{{ __('store.order_received') }}</h1>
        <p>{{ __('store.review_note') }}</p>
        <p>{{ __('store.reference') }}: <bdi dir="ltr" class="font-bold">{{ $order->reference }}</bdi></p>
        <p>{{ __('store.total') }}: <x-price :amount="$order->customer_total" class="font-bold" /></p>
        <a href="{{ url('/') }}" class="btn-primary">{{ __('store.back_home') }}</a>
    </div>
</x-layouts.store>
