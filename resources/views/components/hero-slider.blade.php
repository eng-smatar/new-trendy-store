@props(['products'])
{{-- Native scroll-snap (swipe works) plus Alpine for arrows, dots and autoplay. --}}
<section
    class="relative" aria-roledescription="carousel" aria-label="{{ __('store.slider') }}"
    x-data="{
        current: 0, total: {{ $products->count() }}, playing: ! matchMedia('(prefers-reduced-motion: reduce)').matches, held: false, timer: null,
        init() { this.timer = setInterval(() => { if (this.playing && ! this.held && ! document.hidden) this.go(this.current + 1) }, 6000) },
        destroy() { clearInterval(this.timer) },
        go(i) {
            const track = this.$refs.track, slide = track.children[(i + this.total) % this.total];
            {{-- Offset between boxes: same code for right-to-left, and never scrolls the page. --}}
            track.scrollBy({ left: slide.getBoundingClientRect().left - track.getBoundingClientRect().left, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        },
        sync() { this.current = Math.round(Math.abs(this.$refs.track.scrollLeft) / this.$refs.track.clientWidth) },
    }"
    x-on:mouseenter="held = true" x-on:mouseleave="held = false" x-on:focusin="held = true" x-on:focusout="held = false"
    x-on:touchstart.passive="held = true" x-on:touchend.passive="held = false"
>
    <div x-ref="track" x-on:scroll.debounce.80ms="sync()" class="flex snap-x snap-mandatory overflow-x-auto rounded-2xl [scrollbar-width:none]">
        @foreach ($products as $i => $product)
            <div class="flex h-96 w-full shrink-0 snap-start flex-col bg-[linear-gradient(135deg,var(--brand),color-mix(in_srgb,var(--brand)_60%,black))] text-white sm:h-80 sm:flex-row-reverse"
                 role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $products->count() }}" x-bind:inert="current !== {{ $i }}">
                <div class="min-h-0 flex-1 bg-white p-3 sm:w-2/5 sm:flex-none">
                    <img src="{{ $product->cover_image }}" alt="" class="size-full object-contain" @if ($i > 0) loading="lazy" @else fetchpriority="high" @endif>
                </div>
                <div class="flex flex-col items-start justify-center gap-2 p-4 pb-12 sm:flex-1 sm:px-20">
                    <p class="line-clamp-2 text-lg font-bold sm:text-3xl" dir="auto">{{ $product->name }}</p>
                    <x-price :amount="$product->selling_price" class="text-xl font-bold sm:text-2xl" />
                    <a href="{{ url('/products/'.$product->id) }}" class="btn bg-white text-stone-900 hover:bg-stone-100">{{ __('store.shop') }}</a>
                </div>
            </div>
        @endforeach
    </div>

    @if ($products->count() > 1)
        {{-- ‹ and › are mirrored by the browser in right-to-left, so both point outward. --}}
        <button type="button" x-on:click="go(current - 1)" class="absolute start-2 top-1/2 hidden size-11 -translate-y-1/2 rounded-full bg-white/90 text-xl text-stone-900 shadow sm:block" aria-label="{{ __('store.previous') }}"><span aria-hidden="true">‹</span></button>
        <button type="button" x-on:click="go(current + 1)" class="absolute end-2 top-1/2 hidden size-11 -translate-y-1/2 rounded-full bg-white/90 text-xl text-stone-900 shadow sm:block" aria-label="{{ __('store.next') }}"><span aria-hidden="true">›</span></button>

        <div class="absolute inset-x-0 bottom-1 flex items-center justify-center">
            @foreach ($products as $i => $product)
                <button type="button" x-on:click="go({{ $i }})" class="grid h-11 w-8 place-items-center" aria-label="{{ __('store.slide_n', ['n' => $i + 1]) }}" x-bind:aria-current="current === {{ $i }}">
                    <span class="h-2.5 rounded-full bg-white shadow transition-all" x-bind:class="current === {{ $i }} ? 'w-6' : 'w-2.5 opacity-60'"></span>
                </button>
            @endforeach
            <button type="button" x-on:click="playing = ! playing" class="grid size-11 place-items-center text-sm text-white drop-shadow"
                    x-bind:aria-label="playing ? '{{ __('store.pause') }}' : '{{ __('store.play') }}'">
                <span aria-hidden="true" x-text="playing ? '❚❚' : '▶'">❚❚</span>
            </button>
        </div>
    @endif
</section>
