<div class="mx-auto max-w-md">
    <h1 class="mb-4 text-2xl font-bold">{{ __('store.track') }}</h1>
    <form wire:submit="find" class="card space-y-3 p-4" novalidate>
        @foreach (['reference' => __('store.reference'), 'phone' => __('store.fields.phone')] as $field => $label)
            <div>
                <label for="{{ $field }}" class="label">{{ $label }}</label>
                <input id="{{ $field }}" wire:model="{{ $field }}" class="field" dir="ltr">
                @error($field)<p class="error" role="alert">{{ $message }}</p>@enderror
            </div>
        @endforeach
        <button class="btn-primary w-full" wire:loading.attr="disabled">{{ __('store.search') }}</button>
    </form>

    <div role="status">
        @if ($notice)<p class="card mt-4 p-4">{{ $notice }}</p>@endif
        @if ($result)
            <div class="card mt-4 space-y-2 p-4">
                <p>{{ __('store.reference') }}: <bdi dir="ltr">{{ $result['reference'] }}</bdi></p>
                <p>{{ __('store.status') }}: <strong>{{ $result['status'] }}</strong></p>
                <p>{{ __('store.total') }}: <x-price :amount="$result['total']" /></p>
            </div>
        @endif
    </div>
</div>
