@php
    /** @var \App\Models\Document $record */
    $record = $getRecord();
@endphp

<div class="flex items-center justify-center">
    @if ($record->est_image)
        <img
            src="{{ $record->url }}"
            alt="{{ $record->nom_fichier }}"
            class="h-12 w-12 rounded-lg object-cover border border-gray-200 dark:border-white/10"
        />
    @elseif ($record->est_pdf)
        <div class="relative h-12 w-12 rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden bg-white">
            <embed
                src="{{ $record->url }}#page=1&view=Fit"
                type="application/pdf"
                class="h-full w-full pointer-events-none scale-150 origin-top-left"
            />
        </div>
    @else
        <div class="h-12 w-12 rounded-lg border border-gray-200 dark:border-white/10 flex items-center justify-center bg-gray-50 dark:bg-white/5">
            <x-filament::icon
                :icon="$record->icon"
                class="h-6 w-6 text-gray-400"
            />
        </div>
    @endif
</div>