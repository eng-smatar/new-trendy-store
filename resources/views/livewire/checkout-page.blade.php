@use(App\Models\Setting)
<div class="mx-auto max-w-xl">
    <h1 class="mb-4 text-2xl font-bold">{{ __('store.checkout') }}</h1>

    @if (! Setting::get('ordering_enabled'))
        <p class="card p-4" role="status">{{ Setting::get('closed_message') }}</p>
    @elseif ($lines->isEmpty())
        <div class="py-16 text-center">
            <p class="mb-4 text-stone-600">{{ __('store.cart_empty') }}</p>
            <a href="{{ url('/') }}" class="btn-primary">{{ __('store.back_home') }}</a>
        </div>
    @else
        <form wire:submit="place" class="space-y-4" novalidate>
            <div class="card grid gap-3 p-4 sm:grid-cols-2">
                @foreach (['first_name' => 'text', 'last_name' => 'text', 'phone' => 'tel', 'email' => 'email'] as $field => $type)
                    <div>
                        <label for="{{ $field }}" class="label">{{ __("store.fields.$field") }}</label>
                        <input id="{{ $field }}" type="{{ $type }}" wire:model="{{ $field }}" class="field"
                               @if (in_array($type, ['tel', 'email'])) dir="ltr" @endif
                               @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror>
                        @error($field)<p id="{{ $field }}-error" class="error" role="alert">{{ $message }}</p>@enderror
                    </div>
                @endforeach
                <fieldset class="sm:col-span-2">
                    <legend class="label">{{ __('store.fields.gender') }}</legend>
                    <div class="flex gap-6">
                        @foreach (['male', 'female'] as $value)
                            <label class="flex min-h-11 items-center gap-2"><input type="radio" wire:model="gender" value="{{ $value }}" class="size-5">{{ __("store.$value") }}</label>
                        @endforeach
                    </div>
                    @error('gender')<p class="error" role="alert">{{ $message }}</p>@enderror
                </fieldset>
            </div>

            <div class="card space-y-3 p-4">
                <div>
                    <label for="district_id" class="label">{{ __('store.fields.district_id') }}</label>
                    <select id="district_id" wire:model.live="district_id" class="field">
                        <option value="">{{ __('store.choose_district') }}</option>
                        @foreach ($districts as $district)
                            <option value="{{ $district->district_id }}">{{ $district->district_name }}</option>
                        @endforeach
                    </select>
                    @error('district_id')<p class="error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="town_id" class="label">{{ __('store.fields.town_id') }}</label>
                    <select id="town_id" wire:key="towns-{{ $district_id }}" wire:model.live="town_id" class="field" @disabled($district_id === '')>
                        <option value="">{{ $district_id === '' ? __('store.district_first') : __('store.choose_town') }}</option>
                        @foreach ($towns as $town)
                            <option value="{{ $town->id }}">{{ $town->name }}</option>
                        @endforeach
                    </select>
                    @error('town_id')<p class="error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="address_line1" class="label">{{ __('store.fields.address_line1') }}</label>
                    <input id="address_line1" type="text" wire:model="address_line1" class="field">
                    @error('address_line1')<p class="error" role="alert">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="notes" class="label">{{ __('store.fields.notes') }}</label>
                    <textarea id="notes" wire:model="notes" rows="2" class="field py-2"></textarea>
                    @error('notes')<p class="error" role="alert">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="card space-y-2 p-4" aria-live="polite">
                <div class="flex justify-between"><span>{{ __('store.subtotal') }}</span><x-price :amount="$figures['products_total'] ?? $subtotal" /></div>
                <div class="flex justify-between">
                    <span>{{ __('store.delivery') }}</span>
                    @if (! $figures)
                        <span class="text-stone-500">{{ __('store.choose_town') }}</span>
                    @elseif ($figures['free_shipping'])
                        <span class="font-bold text-green-700">{{ __('store.free') }}</span>
                    @else
                        <x-price :amount="$figures['shipping_fee']" />
                    @endif
                </div>
                @if ($figures)
                    <div class="flex justify-between border-t border-stone-200 pt-2 text-lg font-bold"><span>{{ __('store.total') }}</span><x-price :amount="$figures['customer_total']" /></div>
                @endif
                <p class="text-sm text-stone-600">{{ Setting::get('delivery_estimate') }} · {{ __('store.cod') }}</p>
            </div>

            @if ($notice)
                <p class="card border-amber-300 bg-amber-50 p-3" role="alert">{{ $notice }}</p>
            @endif

            <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" @disabled(! $figures)>{{ __('store.confirm_order') }}</button>
        </form>
    @endif
</div>
