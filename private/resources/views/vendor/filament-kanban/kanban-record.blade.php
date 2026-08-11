@php
    $subtitle = collect([$record->secteur_activite ?? null, $record->ville ?? null])
        ->filter()
        ->implode(' · ');
    $commercial = $record->relationLoaded('commercial') ? $record->commercial : null;
@endphp

<div
    id="{{ $record->getKey() }}"
    wire:click="recordClicked('{{ $record->getKey() }}', {{ @json_encode($record) }})"
    class="record bg-white dark:bg-gray-700 rounded-lg shadow-sm px-3 py-2.5 cursor-grab border-l-4 pk-card-{{ $colorKey }} hover:shadow-md transition-shadow"
    @if($record->timestamps && now()->diffInSeconds($record->{$record::UPDATED_AT}, true) < 3)
        x-data
        x-init="
            $el.classList.add('animate-pulse-twice', 'bg-primary-100', 'dark:bg-primary-800')
            $el.classList.remove('bg-white', 'dark:bg-gray-700')
            setTimeout(() => {
                $el.classList.remove('bg-primary-100', 'dark:bg-primary-800')
                $el.classList.add('bg-white', 'dark:bg-gray-700')
            }, 3000)
        "
    @endif
>
    <p class="font-semibold text-sm text-gray-800 dark:text-gray-100 truncate">
        {{ $record->{static::$recordTitleAttribute} }}
    </p>

    @if($subtitle)
        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
            {{ $subtitle }}
        </p>
    @endif

    @if($record->telephone)
        <p class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 mt-1.5">
            <x-heroicon-o-phone class="w-3.5 h-3.5 flex-shrink-0" />
            <span class="truncate">{{ $record->telephone }}</span>
        </p>
    @endif

    <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-600">
        <span class="text-[0.6875rem] text-gray-400 dark:text-gray-500">
            {{ $record->created_at?->format('d/m/Y') }}
        </span>

        @if($commercial)
            <span
                title="{{ trim($commercial->prenom.' '.$commercial->nom) }}"
                class="pk-avatar flex items-center justify-center w-5 h-5 rounded-full text-[0.625rem] font-bold flex-shrink-0"
            >
                {{ $commercial->initiales ?? '?' }}
            </span>
        @endif
    </div>
</div>
