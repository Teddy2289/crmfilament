@php
    /** @var \App\Models\Document $record */
@endphp

<div class="flex justify-center items-center min-h-[300px]">
    @if ($record->est_image)
        <img
            src="{{ $record->url }}"
            alt="{{ $record->nom_fichier }}"
            class="max-h-[70vh] rounded-lg shadow"
        />
    @elseif ($record->est_pdf)
        <iframe
            src="{{ $record->url }}"
            class="w-full h-[70vh] rounded-lg border border-gray-200 dark:border-white/10"
        ></iframe>
    @else
        <div class="text-center py-12">
            <x-filament::icon
                :icon="$record->icon"
                class="h-16 w-16 mx-auto text-gray-400 mb-4"
            />
            <p class="text-gray-500">Aperçu non disponible pour ce type de fichier.</p>
            <p class="text-sm text-gray-400 mt-1">{{ strtoupper($record->extension) }} — {{ $record->taille_formatee }}</p>
        </div>
    @endif
</div>