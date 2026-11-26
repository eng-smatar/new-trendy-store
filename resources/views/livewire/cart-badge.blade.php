<a href="{{ url('/cart') }}" class="btn-primary px-4">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="size-5" aria-hidden="true"><path d="M6 8h12l1 12H5L6 8Z"/><path d="M9 8a3 3 0 0 1 6 0"/></svg>
    {{ __('store.cart') }}
    <span class="grid min-w-6 place-items-center rounded-full bg-white px-1.5 text-sm font-bold text-brand" aria-live="polite"
          x-data="{ bump: false }" x-on:cart-updated.window="bump = true" x-on:animationend="bump = false" x-bind:class="bump && 'bump'">{{ $count }}</span>
</a>
