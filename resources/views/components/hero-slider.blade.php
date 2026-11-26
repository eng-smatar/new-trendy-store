@props(['products'])
{{-- Native scroll-snap (swipe works) plus Alpine for arrows, dots and autoplay. --}}
<div
    aria-roledescription="carousel" role="region" aria-label="{{ __('store.slider') }}"
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
    <div x-ref="track" x-on:scroll.debounce.80ms="sync()" class="flex snap-x snap-mandatory overflow-x-auto rounded-xl bg-soft [scrollbar-width:none]">
        @foreach ($products as $i => $product)
            <div class="flex h-[26rem] w-full shrink-0 snap-start flex-col md:h-[28rem] md:flex-row-reverse"
                 role="group" aria-roledescription="slide" aria-label="{{ $i + 1 }} / {{ $products->count() }}" x-bind:inert="current !== {{ $i }}">
                {{-- Mart photos sit on white, so the photo half is white and the words half is not. --}}
                <a href="{{ url('/products/'.$product->id) }}" tabindex="-1" aria-hidden="true" class="min-h-0 flex-1 border border-line bg-white p-4 md:w-3/5 md:flex-none md:rounded-e-xl">
                    <img src="{{ $product->cover_image }}" alt="" class="size-full object-contain" {{-- All five load now: lazy ones arrived blank when autoplay reached them. --}} @if ($i === 0) fetchpriority="high" @endif>
                </a>
                <div class="flex flex-col items-start justify-center gap-3 p-5 md:flex-1 md:p-12">
                    @if ($product->category)<p class="text-sm font-medium text-muted">{{ $product->category->label() }}</p>@endif
                    <p class="title-2 line-clamp-2 w-full text-right" dir="auto">{{ $product->name }}</p>
                    <x-price :amount="$product->selling_price" class="title-2 text-brand" />
                    <a href="{{ url('/products/'.$product->id) }}" class="btn-ghost">{{ __('store.view_product') }}</a>
                </div>
            </div>
        @endforeach
    </div>

    @if ($products->count() > 1)
        <div class="mt-3 flex items-center justify-between gap-4">
            <div class="flex items-center">
                @foreach ($products as $i => $product)
                    <button type="button" x-on:click="go({{ $i }})" class="grid h-11 w-11 place-items-center" aria-label="{{ __('store.slide_n', ['n' => $i + 1]) }}" x-bind:aria-current="current === {{ $i }}">
                        <span class="h-2 rounded-full bg-brand transition-all" x-bind:class="current === {{ $i }} ? 'w-6' : 'w-2 opacity-30'"></span>
                    </button>
                @endforeach
            </div>
            <div class="flex gap-2">
                <button type="button" x-on:click="playing = ! playing" class="btn-ghost size-11 px-0"
                        x-bind:aria-label="playing ? '{{ __('store.pause') }}' : '{{ __('store.play') }}'">
                    <span aria-hidden="true" class="text-sm" x-text="playing ? '❚❚' : '▶'">❚❚</span>
                </button>
                {{-- ‹ and › are mirrored by the browser in right-to-left, so both point outward. --}}
                <button type="button" x-on:click="go(current - 1)" class="btn-ghost hidden size-11 px-0 text-xl md:inline-flex" aria-label="{{ __('store.previous') }}"><span aria-hidden="true">‹</span></button>
                <button type="button" x-on:click="go(current + 1)" class="btn-ghost hidden size-11 px-0 text-xl md:inline-flex" aria-label="{{ __('store.next') }}"><span aria-hidden="true">›</span></button>
            </div>
        </div>
    @endif
</div>
