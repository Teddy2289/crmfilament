@props(['status'])

@php
    // Normalise vers l'une des 6 couleurs pour lesquelles kanban-board.blade.php
    // définit les classes .pk-* (repli sur gris si une couleur inattendue arrive).
    $colorKey = in_array($status['color'] ?? null, ['gray', 'blue', 'orange', 'green', 'indigo', 'red'], true)
        ? $status['color']
        : 'gray';

    // Pagination "charger plus" : $status['records'] contient TOUS les
    // enregistrements de la colonne (nécessaire pour le compteur du header),
    // on n'en affiche/rend glissables qu'une tranche à la fois.
    $totalInStatus = count($status['records']);
    $visibleCount = $this->visibleCountFor($status['id']);
    $visibleRecords = array_slice($status['records'], 0, $visibleCount);
    $remaining = $totalInStatus - count($visibleRecords);
@endphp

<div class="md:w-[24rem] flex-shrink-0 mb-5 md:min-h-full flex flex-col">
    @include(static::$headerView)

    <div
        data-status-id="{{ $status['id'] }}"
        class="flex flex-col flex-1 gap-2 p-3 rounded-xl border pk-col-{{ $colorKey }}"
    >
        @foreach($visibleRecords as $record)
            @include(static::$recordView)
        @endforeach
    </div>

    @if($remaining > 0)
        <button
            wire:click="loadMore('{{ $status['id'] }}')"
            wire:loading.attr="disabled"
            class="mt-2 mx-1 py-1.5 text-xs font-semibold rounded-lg pk-badge-{{ $colorKey }} hover:opacity-80 transition-opacity"
        >
            Charger plus ({{ $remaining }} restant{{ $remaining > 1 ? 's' : '' }})
        </button>
    @endif
</div>
