@props(['prospects', 'selectedIds' => []])

{{--
    Composant : queue-table
    Contient :
      - Légende des statuts
      - Barre de sélection multiple (bulk actions)
      - En-tête colonnes
      - Corps : liste triable SortableJS (drag & drop + boutons de réorganisation)
      - Footer avec statistiques

    Props :
      - prospects   : array  — liste formatée des prospects (prospectList)
      - selectedIds : array  — IDs sélectionnés (wire:model depuis HasQueueManagement)

    Actions disponibles via wire:click (fournies par HasQueueManagement) :
      moveUp(id), moveDown(id), moveToTop(id), moveToBottom(id)
      moveSelectedToTop(), removeSelected(), reorderFromDrag(orderedIds), resetOrder()

    Champs utilisés par prospect : id, nom, statut, statut_label, departement, ville,
      telephone, rappel_planifie_at, rappel_en_retard, interlocuteur, secteur_activite,
      type_pressenti, nb_salaries, taux_engagement
--}}

@php
    $statDots = [
        'rpc'       => 'dot-rpc',
        'rp'        => 'dot-rp',
        'std_joint' => 'dot-std_joint',
        'ac'        => 'dot-ac',
        'std_nr'    => 'dot-std_nr',
        'cse_nr'    => 'dot-cse_nr',
        'ko'        => 'dot-ko',
    ];
    $statLabels = [
        'rpc'       => 'lbl-rpc',
        'rp'        => 'lbl-rp',
        'std_joint' => 'lbl-std_joint',
        'ac'        => 'lbl-ac',
        'std_nr'    => 'lbl-std_nr',
        'cse_nr'    => 'lbl-cse_nr',
        'ko'        => 'lbl-ko',
    ];
    $avColors  = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p'];
    $nbTotal   = count($prospects);
    $nbRappels = collect($prospects)->whereNotNull('rappel_planifie_at')->count();
    $nbRetard  = collect($prospects)->where('rappel_en_retard', true)->count();
    $nbRpc     = collect($prospects)->where('statut', 'rpc')->count();
    $nbRp      = collect($prospects)->where('statut', 'rp')->count();
@endphp

<div class="pbo-table-wrap">

    {{-- ── Légende statuts + drag hint ─────────────────────────────────── --}}
    <div class="pbo-legend-bar">
        @foreach (['rpc' => 'RPC', 'rp' => 'RP', 'std_joint' => 'STD-Joint', 'ac' => 'AC', 'cse_nr' => 'CSE-NR', 'std_nr' => 'STD-NR'] as $k => $l)
            <div class="pbo-leg-item">
                <div class="pbo-leg-dot {{ 'dot-' . $k }}"></div>
                {{ $l }}
            </div>
        @endforeach
        <div class="pbo-drag-hint">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
            </svg>
            Glisser ⠿ pour réordonner
        </div>
    </div>

    {{-- ── Barre sélection multiple ──────────────────────────────────────── --}}
    <div class="pbo-bulk-bar {{ count($selectedIds) > 0 ? 'visible' : '' }}" id="pbo-bulk-bar">
        <span><span id="pbo-bulk-count">{{ count($selectedIds) }}</span> sélectionné(s)</span>
        <button class="pbo-bulk-btn" onclick="pboDeselectAll()">✕ Désélectionner</button>
        <button class="pbo-bulk-btn" wire:click="moveSelectedToTop">↑ Mettre en tête</button>
        <button class="pbo-bulk-btn pbo-bulk-btn-danger" wire:click="removeSelected">Retirer</button>
    </div>

    {{-- ── En-tête colonnes ──────────────────────────────────────────────── --}}
    <div class="pbo-table-header">
        <div class="pbo-th" style="justify-content:center;">
            <input type="checkbox" class="pbo-checkbox" onchange="pboSelectAll(this)"
                title="Tout sélectionner">
        </div>
        <div class="pbo-th"></div>{{-- grip --}}
        <div class="pbo-th" style="justify-content:center;">#</div>
        <div class="pbo-th">Nom</div>
        <div class="pbo-th">Statut</div>
        <div class="pbo-th">Téléphone</div>
        <div class="pbo-th">Département</div>
        <div class="pbo-th">Rappel</div>
        <div class="pbo-th" style="justify-content:center;">Eng.</div>
    </div>

    {{-- ── Corps du tableau ──────────────────────────────────────────────── --}}
    <div class="pbo-table-body">
        @if ($nbTotal === 0)
            <div class="pbo-empty">
                <div class="pbo-empty-icon">📭</div>
                <div class="pbo-empty-title">Aucun prospect en file</div>
                <div class="pbo-empty-sub">Sélectionnez un téléprospecteur ci-dessus.</div>
            </div>
        @else
            <div id="pbo-sortable-list">
                @foreach ($prospects as $i => $p)
                    @php
                        $rank    = $i + 1;
                        $dotCls  = $statDots[$p['statut']] ?? 'dot-ac';
                        $lblCls  = $statLabels[$p['statut']] ?? 'lbl-ac';
                        $initial = mb_strtolower(mb_substr($p['nom'], 0, 1));
                        $avCls   = in_array($initial, $avColors) ? 'av-' . $initial : 'av-default';
                        $initials = mb_strtoupper(mb_substr($p['nom'], 0, 1));
                    @endphp
                    <div class="pbo-row" wire:key="row-{{ $p['id'] }}"
                         data-id="{{ $p['id'] }}"
                         data-nom="{{ strtolower($p['nom']) }}"
                         data-tel="{{ $p['telephone'] ?? '' }}">

                        {{-- Check --}}
                        <div class="pbo-cell pbo-col-check">
                            <input type="checkbox" class="pbo-checkbox row-check"
                                   value="{{ $p['id'] }}"
                                   {{ in_array($p['id'], $selectedIds) ? 'checked' : '' }}>
                        </div>

                        {{-- Grip (drag handle) --}}
                        <div class="pbo-cell pbo-col-grip" title="Glisser pour réordonner">
                            <svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor">
                                <circle cx="3" cy="2.5"  r="1.1" />
                                <circle cx="3" cy="7"    r="1.1" />
                                <circle cx="3" cy="11.5" r="1.1" />
                                <circle cx="7" cy="2.5"  r="1.1" />
                                <circle cx="7" cy="7"    r="1.1" />
                                <circle cx="7" cy="11.5" r="1.1" />
                            </svg>
                        </div>

                        {{-- Rang --}}
                        <div class="pbo-cell pbo-col-rank {{ $rank <= 3 ? 'r' . $rank : '' }}">
                            {{ $rank }}
                        </div>

                        {{-- Nom --}}
                        <div class="pbo-cell pbo-col-nom">
                            <div class="pbo-nom-avatar {{ $avCls }}">{{ $initials }}</div>
                            <div class="pbo-nom-text">
                                <div class="pbo-nom-name">
                                    {{ $p['nom'] }}
                                    @if ($rank === 1)
                                        <span class="pbo-badge-next">PROCHAIN</span>
                                    @endif
                                </div>
                                <div class="pbo-nom-sub">
                                    @if (!empty($p['interlocuteur']) && $p['interlocuteur'] !== 'Non défini')
                                        {{ $p['interlocuteur'] }}
                                    @elseif (!empty($p['secteur_activite']))
                                        {{ $p['secteur_activite'] }}
                                    @elseif (!empty($p['type_pressenti']) && $p['type_pressenti'] !== 'Non défini')
                                        {{ $p['type_pressenti'] }}
                                    @else
                                        &nbsp;
                                    @endif
                                    @if (!empty($p['nb_salaries']))
                                        · {{ $p['nb_salaries'] }} sal.
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Statut --}}
                        <div class="pbo-cell pbo-col-statut">
                            <div class="pbo-status-dot {{ $dotCls }}"></div>
                            <span class="pbo-status-label {{ $lblCls }}">{{ $p['statut_label'] }}</span>
                            @if ($p['rappel_en_retard'])
                                <span class="pbo-badge-late">⚠</span>
                            @endif
                        </div>

                        {{-- Téléphone --}}
                        <div class="pbo-cell pbo-col-tel">
                            {{ $p['telephone'] ?? '—' }}
                        </div>

                        {{-- Département --}}
                        <div class="pbo-cell pbo-col-dept">
                            @if (!empty($p['ville']) || !empty($p['departement']))
                                <svg width="10" height="10" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24" style="flex-shrink:0;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                </svg>
                                {{ $p['ville'] ?? '' }}{{ !empty($p['departement']) ? ' (' . $p['departement'] . ')' : '' }}
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </div>

                        {{-- Rappel --}}
                        <div class="pbo-cell pbo-col-rappel">
                            @if (!empty($p['rappel_planifie_at']))
                                <span class="{{ $p['rappel_en_retard'] ? 'pbo-rappel-late' : '' }}">
                                    {{ $p['rappel_planifie_at'] }}
                                </span>
                            @else
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </div>

                        {{-- Engagement --}}
                        <div class="pbo-cell pbo-col-eng">
                            {{ $p['taux_engagement'] ?? '—' }}
                        </div>

                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ── Footer statistiques ───────────────────────────────────────────── --}}
    @if ($nbTotal > 0)
        <div class="pbo-footer">
            <div class="pbo-fstat">
                <span class="pbo-fstat-val">{{ $nbTotal }}</span>
                <span>prospect{{ $nbTotal > 1 ? 's' : '' }}</span>
            </div>
            @if ($nbRpc > 0)
                <div class="pbo-fstat">
                    <span class="pbo-fstat-val" style="color:#0d9488;">{{ $nbRpc }}</span>
                    <span>RPC</span>
                </div>
            @endif
            @if ($nbRp > 0)
                <div class="pbo-fstat">
                    <span class="pbo-fstat-val" style="color:#16a34a;">{{ $nbRp }}</span>
                    <span>RP</span>
                </div>
            @endif
            @if ($nbRappels > 0)
                <div class="pbo-fstat">
                    <span class="pbo-fstat-val">{{ $nbRappels }}</span>
                    <span>avec rappel</span>
                </div>
            @endif
            @if ($nbRetard > 0)
                <div class="pbo-fstat" style="color:#dc2626;">
                    <span class="pbo-fstat-val" style="color:#dc2626;">{{ $nbRetard }}</span>
                    <span>en retard</span>
                </div>
            @endif
            <div class="pbo-footer-note">Ordre sauvegardé 24h · Réinitialisé à minuit</div>
        </div>
    @endif

</div>{{-- /pbo-table-wrap --}}
