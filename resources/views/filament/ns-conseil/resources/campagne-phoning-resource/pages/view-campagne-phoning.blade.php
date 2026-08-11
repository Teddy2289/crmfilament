<x-filament-panels::page
    @class([
        'fi-resource-view-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    {{-- Les filtres sont affichés par onglet pour chaque statut (voir la section Résultats des appels) --}}

    {{ $this->infolist }}

    {{ $this->table }}
</x-filament-panels::page>
