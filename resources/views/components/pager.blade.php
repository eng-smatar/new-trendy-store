<div>
    @if ($paginator->hasPages())
        <nav class="flex items-center justify-center gap-3" aria-label="{{ __('store.pages') }}">
            <button type="button" class="btn-ghost" wire:click="previousPage" x-on:click="window.scrollTo(0, 0)" @disabled($paginator->onFirstPage())>{{ __('store.previous') }}</button>
            <span class="text-muted">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            <button type="button" class="btn-ghost" wire:click="nextPage" x-on:click="window.scrollTo(0, 0)" @disabled(! $paginator->hasMorePages())>{{ __('store.next') }}</button>
        </nav>
    @endif
</div>
