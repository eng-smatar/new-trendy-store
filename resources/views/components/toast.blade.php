{{-- Confirmation toast. Dispatch "toast" with a message; the cart count carries the same news. --}}
<div
    role="region" aria-label="{{ __('store.notifications') }}"
    x-data="{
        message: '', timer: null,
        show(message) {
            this.message = message;
            if (this.$refs.toast.showPopover && ! this.$refs.toast.matches(':popover-open')) this.$refs.toast.showPopover();
            this.wait();
        },
        wait() { clearTimeout(this.timer); this.timer = setTimeout(() => this.hide(), 5000) },
        hide() { if (this.$refs.toast.matches(':popover-open')) this.$refs.toast.hidePopover() },
    }"
    x-on:toast.window="show($event.detail.message)"
>
    <p class="sr-only" role="status" x-text="message"></p>
    <div x-ref="toast" popover="manual"
         class="toast fixed inset-x-4 top-20 bottom-auto m-0 w-auto rounded-2xl border border-stone-200 bg-white p-3 text-stone-900 shadow-xl sm:start-auto sm:top-auto sm:bottom-4 sm:w-96"
         x-on:mouseenter="clearTimeout(timer)" x-on:mouseleave="wait()" x-on:focusin="clearTimeout(timer)" x-on:focusout="wait()">
        <div class="flex items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-green-100 text-green-800" aria-hidden="true">✓</span>
            <span class="flex-1 font-medium" x-text="message" aria-hidden="true"></span>
            <a href="{{ url('/cart') }}" class="btn-primary">{{ __('store.view_cart') }}</a>
            <button type="button" x-on:click="hide()" class="grid size-11 place-items-center rounded-full hover:bg-stone-100" aria-label="{{ __('store.close') }}">✕</button>
        </div>
    </div>
</div>
