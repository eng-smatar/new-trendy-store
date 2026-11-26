<div class="wrap grid gap-8 py-8 md:py-16 lg:grid-cols-2 lg:gap-16">
    <div>
    <h1 class="title-1">{{ __('store.track') }}</h1>
    <p class="mt-3 mb-8 text-lg text-muted">{{ __('store.track_lead') }}</p>
    <form wire:submit="find" class="space-y-4" novalidate>
        @foreach (['reference' => __('store.reference'), 'phone' => __('store.fields.phone')] as $field => $label)
            <div>
                <label for="{{ $field }}" class="label">{{ $label }}</label>
                <input id="{{ $field }}" wire:model="{{ $field }}" class="field" dir="ltr">
                @error($field)<p class="error" role="alert">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <button class="btn-primary w-full" wire:loading.attr="disabled">{{ __('store.search') }}</button>
    </form>
    </div>

    <div role="status" class="self-start">
        @if ($notice)<p class="rounded-xl bg-soft p-6">{{ $notice }}</p>@endif
        @if ($result)
            <div class="space-y-3 rounded-xl bg-soft p-6">
                <p>{{ __('store.reference') }}: <bdi dir="ltr">{{ $result['reference'] }}</bdi></p>
                <p>{{ __('store.status') }}: <strong>{{ $result['status'] }}</strong></p>
                <p>{{ __('store.total') }}: <x-price :amount="$result['total']" /></p>
            </div>
        @endif
    </div>
</div>
