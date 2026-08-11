{{-- resources/views/filament/loading-overlay.blade.php --}}

<style>
    [wire\:loading-overlay] {
        display: none;
    }
</style>

<div wire:loading.flex wire:loading.delay.shortest style="display:none"
    class="fixed inset-0 z-[9999] items-center justify-center">
    {{-- Fond flouté --}}
    <div class="absolute inset-0 bg-white/70 dark:bg-gray-900/70 backdrop-blur-sm"></div>

    {{-- Carte centrale --}}
    <div
        class="relative flex flex-col items-center gap-4 rounded-2xl bg-white dark:bg-gray-800 shadow-2xl px-10 py-8 border border-gray-100 dark:border-gray-700">
        <x-filament::loading-indicator class="h-10 w-10 text-primary-600" />
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 tracking-wide">
            Chargement…
        </span>
    </div>
</div>