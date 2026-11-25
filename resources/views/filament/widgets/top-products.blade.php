<x-filament-widgets::widget>
    <x-filament::section :heading="__('admin.top_products')">
        @forelse ($products as $product)
            <div style="display:flex;justify-content:space-between;gap:1rem;padding:.25rem 0">
                <span dir="auto">{{ $product->name }}</span>
                <strong>{{ $product->units }}</strong>
            </div>
        @empty
            <p>{{ __('admin.none') }}</p>
        @endforelse
    </x-filament::section>
</x-filament-widgets::widget>
