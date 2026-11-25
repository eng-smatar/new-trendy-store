<button type="button" x-data="{ shown: false }" x-show="shown" x-cloak x-transition style="display: none"
        x-on:scroll.window.throttle.100ms="shown = window.scrollY > 600"
        x-on:click="window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })"
        class="fixed bottom-24 end-4 z-30 grid size-12 place-items-center rounded-full bg-brand text-xl text-white shadow-lg" aria-label="{{ __('store.back_to_top') }}">
    <span aria-hidden="true">↑</span>
</button>
