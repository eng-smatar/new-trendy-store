<a href="{{ url('/cart') }}" class="btn-ghost ms-auto sm:order-last sm:ms-0">
    {{ __('store.cart') }}
    <span class="rounded-full bg-brand px-2 py-0.5 text-xs text-white" aria-live="polite"
          x-data="{ bump: false }" x-on:cart-updated.window="bump = true" x-on:animationend="bump = false" x-bind:class="bump && 'bump'">{{ $count }}</span>
</a>
