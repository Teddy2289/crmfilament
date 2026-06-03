{{--
    resources/views/filament/ns-conseil/pages/list-prospects.blade.php

    Vue unique pour ListProspects.
    Un seul fichier blade — @if $viewMode switche entre table et kanban.
    Les assets Filament (CSS/JS) sont toujours chargés correctement
    car on reste dans le layout x-filament-panels::page standard.
--}}
<x-filament-panels::page>

    @push('styles')
    <style>
        /* ── Toggle Table / Kanban ──────────────────────────────────── */
        .kb-toggle {
            display: inline-flex;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.5rem;
            overflow: hidden;
            background: rgb(249 250 251);
        }
        .dark .kb-toggle {
            border-color: rgb(55 65 81);
            background: rgb(31 41 55);
        }
        .kb-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.35rem 0.875rem;
            font-size: 0.8125rem;
            font-weight: 600;
            border: none;
            background: transparent;
            cursor: pointer;
            color: rgb(107 114 128);
            transition: color 0.12s;
        }
        .dark .kb-toggle-btn { color: rgb(156 163 175); }
        .kb-toggle-btn:hover { color: rgb(17 24 39); }
        .dark .kb-toggle-btn:hover { color: rgb(243 244 246); }
        .kb-toggle-btn-active {
            background: white;
            color: rgb(17 24 39);
            border-radius: 0.375rem;
            margin: 0.125rem;
            box-shadow: 0 1px 2px rgb(0 0 0 / .08);
        }
        .dark .kb-toggle-btn-active {
            background: rgb(55 65 81);
            color: rgb(243 244 246);
        }

        /* ── Kanban ─────────────────────────────────────────────────── */
        .kb-wrap { display: flex; flex-direction: column; gap: 1.25rem; }
        .kb-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .kb-stats { display: flex; gap: 1rem; flex-wrap: wrap; }
        .kb-stat {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.8125rem;
            color: rgb(107 114 128);
        }
        .dark .kb-stat { color: rgb(156 163 175); }
        .kb-stat strong { color: rgb(17 24 39); font-weight: 600; }
        .dark .kb-stat strong { color: rgb(243 244 246); }

        .kb-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .kb-dot-primary { background: rgb(59 130 246); }
        .kb-dot-success { background: rgb(34 197 94); }
        .kb-dot-warning { background: rgb(234 179 8); }
        .kb-dot-danger  { background: rgb(239 68 68); }
        .kb-dot-gray    { background: rgb(107 114 128); }
        .kb-dot-info    { background: rgb(6 182 212); }
        .kb-dot-orange  { background: rgb(249 115 22); }

        .kb-board {
            display: flex;
            gap: 0.625rem;
            overflow-x: auto;
            padding-bottom: 0.75rem;
            align-items: flex-start;
        }
        .kb-board::-webkit-scrollbar { height: 4px; }
        .kb-board::-webkit-scrollbar-track { background: transparent; }
        .kb-board::-webkit-scrollbar-thumb { background: rgb(209 213 219); border-radius: 2px; }
        .dark .kb-board::-webkit-scrollbar-thumb { background: rgb(55 65 81); }

        .kb-col {
            flex-shrink: 0;
            width: 14rem;
            display: flex;
            flex-direction: column;
            border-radius: 0.75rem;
            background: rgb(248 250 252);
            border: 1px solid rgb(226 232 240);
            overflow: hidden;
        }
        .dark .kb-col { background: rgb(15 23 42); border-color: rgb(30 41 59); }

        .kb-col-primary { border-top: 2px solid rgb(59 130 246); }
        .kb-col-success { border-top: 2px solid rgb(34 197 94); }
        .kb-col-warning { border-top: 2px solid rgb(234 179 8); }
        .kb-col-danger  { border-top: 2px solid rgb(239 68 68); }
        .kb-col-gray    { border-top: 2px solid rgb(107 114 128); }
        .kb-col-info    { border-top: 2px solid rgb(6 182 212); }
        .kb-col-orange  { border-top: 2px solid rgb(249 115 22); }

        .kb-col-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.625rem 0.75rem;
            border-bottom: 1px solid rgb(226 232 240);
        }
        .dark .kb-col-hdr { border-bottom-color: rgb(30 41 59); }

        .kb-col-title {
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: rgb(71 85 105);
        }
        .dark .kb-col-title { color: rgb(148 163 184); }

        .kb-col-count {
            font-size: 0.6875rem;
            font-weight: 700;
            padding: 0.125rem 0.5rem;
            border-radius: 9999px;
            background: white;
            color: rgb(71 85 105);
            border: 1px solid rgb(226 232 240);
        }
        .dark .kb-col-count { background: rgb(30 41 59); color: rgb(148 163 184); border-color: rgb(51 65 85); }

        .kb-col-body {
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            gap: 0.375rem;
            min-height: 3rem;
            max-height: calc(100vh - 14rem);
            overflow-y: auto;
        }
        .kb-col-body::-webkit-scrollbar { width: 3px; }
        .kb-col-body::-webkit-scrollbar-thumb { background: rgb(203 213 225); border-radius: 2px; }
        .dark .kb-col-body::-webkit-scrollbar-thumb { background: rgb(51 65 85); }

        .kb-card {
            background: white;
            border: 1px solid rgb(226 232 240);
            border-radius: 0.5rem;
            padding: 0.625rem 0.75rem;
            cursor: pointer;
            transition: border-color 0.12s, box-shadow 0.12s;
            text-decoration: none;
            display: block;
        }
        .dark .kb-card { background: rgb(30 41 59); border-color: rgb(51 65 85); }
        .kb-card:hover { border-color: rgb(148 163 184); box-shadow: 0 2px 6px rgb(0 0 0/.06); }
        .dark .kb-card:hover { border-color: rgb(100 116 139); box-shadow: 0 2px 6px rgb(0 0 0/.2); }

        .kb-card-name {
            font-weight: 600;
            font-size: 0.8125rem;
            margin: 0 0 0.25rem;
            color: rgb(15 23 42);
            line-height: 1.3;
        }
        .dark .kb-card-name { color: rgb(226 232 240); }

        .kb-card-meta {
            font-size: 0.6875rem;
            color: rgb(100 116 139);
            display: flex;
            align-items: center;
            gap: 3px;
        }

        .kb-card-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid rgb(241 245 249);
        }
        .dark .kb-card-footer { border-top-color: rgb(30 41 59); }

        .kb-badge {
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            padding: 1px 5px;
            border-radius: 3px;
            background: rgb(241 245 249);
            color: rgb(71 85 105);
            border: 1px solid rgb(226 232 240);
        }
        .dark .kb-badge { background: rgb(30 41 59); color: rgb(148 163 184); border-color: rgb(51 65 85); }

        .kb-rappel-late {
            font-size: 0.625rem;
            font-weight: 600;
            color: rgb(220 38 38);
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        .dark .kb-rappel-late { color: rgb(248 113 113); }

        .kb-rappel-ok {
            font-size: 0.625rem;
            font-weight: 600;
            color: rgb(16 185 129);
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }

        .kb-tele {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: rgb(219 234 254);
            color: rgb(37 99 235);
            font-size: 0.5625rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .dark .kb-tele { background: rgb(30 58 138); color: rgb(147 197 253); }

        .kb-empty {
            text-align: center;
            padding: 1.25rem 0.5rem;
            font-size: 0.75rem;
            color: rgb(148 163 184);
        }
        .kb-empty svg { margin: 0 auto 0.25rem; display: block; opacity: .35; }
    </style>
    @endpush

    {{-- ════════════════════════════════════════════════════════════
         MODE TABLE
         On laisse Filament rendre sa table native.
         On ajoute uniquement le toggle au-dessus.
    ════════════════════════════════════════════════════════════ --}}
    @if($viewMode === 'table')

        <div style="display:flex; justify-content:flex-end; margin-bottom:0.75rem;">
            <div class="kb-toggle">
                <button wire:click="switchView('table')" class="kb-toggle-btn kb-toggle-btn-active">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                        <path d="M3 9h18M3 15h18M9 3v18"/>
                    </svg>
                    Table
                </button>
                <button wire:click="switchView('kanban')" class="kb-toggle-btn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="3" y="3" width="5" height="18" rx="1"/>
                        <rect x="10" y="3" width="5" height="12" rx="1"/>
                        <rect x="17" y="3" width="5" height="15" rx="1"/>
                    </svg>
                    Kanban
                </button>
            </div>
        </div>

        {{-- Onglets Filament natifs --}}
        <x-filament-panels::resources.tabs />

        {{-- Table Filament native — on ne touche à rien --}}
        {{ $this->table }}

    @else
    {{-- ════════════════════════════════════════════════════════════
         MODE KANBAN
    ════════════════════════════════════════════════════════════ --}}

        <div class="kb-wrap">

            {{-- Toolbar --}}
            <div class="kb-toolbar">
                <div class="kb-stats">
                    @php $totalAffiche = array_sum(array_map(fn($g) => $g['prospects']->count(), $kanbanGroups ?? [])); @endphp
                    <div class="kb-stat">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span><strong>{{ $totalAffiche }}</strong> prospects</span>
                    </div>
                    @foreach($kanbanGroups ?? [] as $group)
                        @if($group['prospects']->count() > 0)
                            <div class="kb-stat">
                                <div class="kb-dot kb-dot-{{ $group['color'] }}"></div>
                                <span>{{ $group['label'] }} : <strong>{{ $group['prospects']->count() }}</strong></span>
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="kb-toggle">
                    <button wire:click="switchView('table')" class="kb-toggle-btn">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <path d="M3 9h18M3 15h18M9 3v18"/>
                        </svg>
                        Table
                    </button>
                    <button wire:click="switchView('kanban')" class="kb-toggle-btn kb-toggle-btn-active">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="3" y="3" width="5" height="18" rx="1"/>
                            <rect x="10" y="3" width="5" height="12" rx="1"/>
                            <rect x="17" y="3" width="5" height="15" rx="1"/>
                        </svg>
                        Kanban
                    </button>
                </div>
            </div>

            {{-- Board --}}
            <div class="kb-board">
                @foreach($kanbanGroups ?? [] as $group)
                    @php
                        $color     = $group['color'];
                        $prospects = $group['prospects'];
                        $count     = $prospects->count();
                    @endphp

                    <div class="kb-col kb-col-{{ $color }}">

                        <div class="kb-col-hdr">
                            <div style="display:flex;align-items:center;gap:5px;">
                                <div class="kb-dot kb-dot-{{ $color }}"></div>
                                <span class="kb-col-title">{{ $group['label'] }}</span>
                            </div>
                            <span class="kb-col-count">{{ $count }}</span>
                        </div>

                        <div class="kb-col-body">
                            @forelse($prospects as $prospect)
                                @php
                                    $rappelLate = $prospect->rappel_planifie_at
                                        && $prospect->rappel_planifie_at instanceof \Carbon\Carbon
                                        && $prospect->rappel_planifie_at->isPast();
                                    $rappelSoon = $prospect->rappel_planifie_at && !$rappelLate;
                                    $editUrl    = \App\Filament\NsConseil\Resources\ProspectResource::getUrl('edit', ['record' => $prospect->id]);
                                    $tele       = $prospect->teleprospecteur;
                                    $teleInit   = $tele ? strtoupper(substr($tele->prenom ?? '', 0, 1) . substr($tele->nom ?? '', 0, 1)) : null;
                                @endphp

                                <a href="{{ $editUrl }}" class="kb-card">
                                    <p class="kb-card-name">{{ $prospect->nom }}</p>

                                    @if($prospect->departement || $prospect->telephone)
                                        <div class="kb-card-meta">
                                            @if($prospect->departement)
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
                                                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                                                    <circle cx="12" cy="9" r="2.5"/>
                                                </svg>
                                                {{ $prospect->departement }}
                                            @endif
                                            @if($prospect->telephone)
                                                @if($prospect->departement)<span style="opacity:.35;">·</span>@endif
                                                {{ $prospect->telephone }}
                                            @endif
                                        </div>
                                    @endif

                                    @if($prospect->interlocuteur_nom)
                                        <div class="kb-card-meta" style="margin-top:2px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
                                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                                <circle cx="12" cy="7" r="4"/>
                                            </svg>
                                            {{ $prospect->interlocuteur_nom }}
                                        </div>
                                    @endif

                                    <div class="kb-card-footer">
                                        <div style="display:flex;align-items:center;gap:4px;">
                                            <span class="kb-badge">
                                                {{ $prospect->type_pressenti ? ucfirst(str_replace('_', ' ', $prospect->type_pressenti)) : '—' }}
                                            </span>
                                            @if($teleInit)
                                                <span class="kb-tele" title="{{ $tele->prenom }} {{ $tele->nom }}">{{ $teleInit }}</span>
                                            @endif
                                        </div>

                                        @if($rappelLate)
                                            <span class="kb-rappel-late">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <circle cx="12" cy="12" r="10"/>
                                                    <path d="M12 6v6l4 2"/>
                                                </svg>
                                                {{ $prospect->rappel_planifie_at->format('d/m') }}
                                            </span>
                                        @elseif($rappelSoon)
                                            <span class="kb-rappel-ok">
                                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                                                    <path d="M16 2v4M8 2v4M3 10h18"/>
                                                </svg>
                                                {{ $prospect->rappel_planifie_at->format('d/m') }}
                                            </span>
                                        @endif
                                    </div>
                                </a>

                            @empty
                                <div class="kb-empty">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M22 12h-6l-2 3h-4l-2-3H2"/>
                                        <path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/>
                                    </svg>
                                    Aucun prospect
                                </div>
                            @endforelse
                        </div>

                    </div>
                @endforeach
            </div>

        </div>

    @endif

</x-filament-panels::page>
