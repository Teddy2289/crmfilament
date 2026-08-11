{{--
    resources/views/filament/ns-conseil/pages/prospects-table.blade.php

    Vue "table" pour ListProspects.
    On étend la vue Filament list-records standard en ajoutant juste
    le toggle Table/Kanban au-dessus de la table.

    IMPORTANT : on réutilise le composant Filament natif pour la table
    afin de ne rien casser (pagination, filtres, colonnes, etc.)
--}}
<x-filament-panels::page>

    @push('styles')
    <style>
        .fi-view-toggle {
            display: inline-flex;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.625rem;
            overflow: hidden;
            background: rgb(249 250 251);
        }
        .dark .fi-view-toggle {
            border-color: rgb(55 65 81);
            background: rgb(31 41 55);
        }
        .fi-view-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border: none;
            background: transparent;
            cursor: pointer;
            color: rgb(107 114 128);
            transition: all 0.15s;
        }
        .dark .fi-view-toggle-btn { color: rgb(156 163 175); }
        .fi-view-toggle-btn:hover { color: rgb(37 99 235); }
        .fi-view-toggle-btn-active {
            background: white;
            color: rgb(37 99 235);
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
            border-radius: 0.5rem;
            margin: 0.125rem;
        }
        .dark .fi-view-toggle-btn-active {
            background: rgb(55 65 81);
            color: rgb(96 165 250);
        }
    </style>
    @endpush

    {{-- Toggle aligné à droite, au-dessus des onglets --}}
    <div style="display:flex; justify-content:flex-end; margin-bottom:0.75rem;">
        <div class="fi-view-toggle">
            <button wire:click="switchView('table')"
                    class="fi-view-toggle-btn fi-view-toggle-btn-active">
                <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 4v16M3 4h18v16H3z"/>
                </svg>
                Table
            </button>
            <button wire:click="switchView('kanban')"
                    class="fi-view-toggle-btn">
                <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                Kanban
            </button>
        </div>
    </div>

    {{-- Onglets Filament natifs --}}
    <x-filament-panels::resources.tabs />

    {{-- Table Filament native — rendu standard, on ne touche à rien --}}
    {{ $this->table }}

</x-filament-panels::page>
