@props([
    'statuts',          // array — statuts groupés par cas (getStatutsPhoningGroupes())
    'selectedStatut',   // string — code du statut sélectionné (statut_resultat)
    'commentaires',     // string — valeur courante du champ commentaires
    'rappelDate',       // string — valeur courante de rappel_date
    'rappelHeure',      // string — valeur courante de rappel_heure
    'rappelCodes'   => [],   // array  — codes déclenchant l'affichage de la rappel box
    'pipelinePreview' => null, // array|null — aperçu de la transition pipeline
])

{{--
    Composant : status-panel
    Contient :
      - Onglets "cas" (groupes de statuts CSE v2)
      - Chips de sélection de statut (une par groupe actif)
      - Aperçu de transition pipeline (si $pipelinePreview non null)
      - Rappel box (date + heure, conditionnellement visible)
      - Textarea commentaires + messages d'aide
      - Boutons Enregistrer / Passer

    Props :
      - statuts         : array       — statuts groupés par cas (getStatutsPhoningGroupes())
      - selectedStatut  : string      — code du statut sélectionné (bind : wire:model="statut_resultat")
      - commentaires    : string      — valeur courante du champ commentaires
      - rappelDate      : string      — valeur courante de rappel_date
      - rappelHeure     : string      — valeur courante de rappel_heure
      - rappelCodes     : array       — codes déclenchant la rappel box (getRappelStatusCodes())
      - pipelinePreview : array|null  — aperçu de transition pipeline (getPipelineTransitionPreview())

    Bindings Livewire (parent) :
      wire:click="$set('statut_resultat', ...)"
      wire:model="statut_resultat"
      wire:model="commentaires"
      wire:model="rappel_date"
      wire:model="rappel_heure"
      wire:click="submitResult"
      wire:click.prevent="skipCall"
--}}

@php
    // Déterminer l'onglet "cas" actif : celui contenant le statut sélectionné, sinon le premier.
    $activeGroupeKey = null;
    foreach ($statuts as $gKey => $g) {
        foreach ($g['statuts'] as $gOption) {
            if ($gOption['value'] === $selectedStatut) {
                $activeGroupeKey = $gKey;
                break 2;
            }
        }
    }
    if ($activeGroupeKey === null) {
        $activeGroupeKey = array_key_first($statuts);
    }

    $isRappelVisible = in_array($selectedStatut, $rappelCodes);
@endphp

{{-- ── RÉSULTAT DE L'APPEL ─────────────────────────────────────────────── --}}
<div class="pw-result-panel pw-card">

    {{-- En-tête --}}
    <div class="pw-result-header">
        <span class="pw-result-header-icon">
            <svg class="pw-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
            </svg>
        </span>
        <span>Résultat de l'appel</span>
    </div>

    {{-- ── Onglets "Cas" ─────────────────────────────────────────────── --}}
    <div class="pw-case-tabs">
        @foreach ($statuts as $groupeKey => $groupe)
        <button type="button"
            class="pw-case-tab {{ $groupeKey === $activeGroupeKey ? 'active' : '' }}"
            data-case-tab="{{ $groupeKey }}"
            onclick="switchCaseTab('{{ $groupeKey }}')">
            {{ $groupe['label'] }}
        </button>
        @endforeach
    </div>

    {{-- ── Chips statuts (une panneaux par groupe) ───────────────────── --}}
    @foreach ($statuts as $groupeKey => $groupe)
    <div class="pw-case-panel {{ $groupeKey === $activeGroupeKey ? 'active' : '' }}"
         data-case-panel="{{ $groupeKey }}">
        <div class="pw-chip-group">
            <div class="pw-chip-row">
                @foreach ($groupe['statuts'] as $option)
                @php
                    $isActive    = $selectedStatut === $option['value'];
                    $optColor    = trim(\Illuminate\Support\Str::after($option['bar'] ?? '', 'background:'));
                    $optionStyle = 'border-left-color: ' . $optColor . ';';
                    if ($isActive) {
                        $optionStyle .= ' ' . ($option['bar'] ?? '') . '; color:white;';
                    }
                @endphp
                <label wire:click="$set('statut_resultat', '{{ $option['value'] }}')"
                    onclick="toggleRappel('{{ $option['value'] }}')"
                    title="{{ $option['label'] }} — {{ $option['sub'] }}{{ $option['action'] ? ' → ' . $option['action'] : '' }}"
                    class="pw-chip"
                    style="{{ $optionStyle }}">
                    <span class="pw-chip-icon">{{ $option['icon'] ?? $option['icone'] ?? '•' }}</span>
                    <span class="pw-chip-main">
                        <span class="pw-chip-label-row {{ $isActive ? 'pw-chip-label-row--active' : '' }}">
                            {{ $option['label'] }}
                            @if (!empty($option['prioritaire']))
                            <span class="pw-chip-star" title="Prioritaire">★</span>
                            @endif
                        </span>
                        <span class="pw-chip-sub" style="{{ $isActive ? 'color:rgba(255,255,255,.85);' : '' }}">
                            {{ $option['sub'] }}{{ $option['action'] ? ' → ' . $option['action'] : '' }}
                        </span>
                        @if (!empty($option['pipeline_label']))
                        <span class="pw-chip-pipeline" style="{{ $isActive ? 'color:rgba(255,255,255,.9);' : 'color:rgb(100 116 139);' }}">
                            Pipeline : {{ $option['pipeline_label'] }}
                        </span>
                        @endif
                    </span>
                    <input type="radio" wire:model="statut_resultat" value="{{ $option['value'] }}" style="display:none;">
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @endforeach

    {{-- ── Aperçu transition pipeline ────────────────────────────────── --}}
    @if ($pipelinePreview)
    <div class="pw-pipeline-link" wire:key="pipeline-preview-{{ $selectedStatut }}">
        <div class="pw-pipeline-link-title">Lien statut d'appel → statut pipeline</div>
        <div class="pw-pipeline-link-flow">
            @if ($pipelinePreview['current'])
            <span class="pw-pipeline-link-step" style="{{ $pipelinePreview['current']['badge_style'] }}">
                Pipeline actuel · {{ $pipelinePreview['current']['label'] }}
            </span>
            @endif
            <span class="pw-pipeline-link-arrow">→</span>
            <span class="pw-pipeline-link-step" style="{{ $pipelinePreview['call_status']['bar'] ?? '' }}; color:white;">
                {{ $pipelinePreview['call_status']['icon'] ?? '•' }} Appel · {{ $pipelinePreview['call_status']['label'] }}
            </span>
            @if ($pipelinePreview['next'])
            <span class="pw-pipeline-link-arrow">→</span>
            <span class="pw-pipeline-link-step" style="{{ $pipelinePreview['next']['badge_style'] }}">
                Pipeline après qualification · {{ $pipelinePreview['next']['label'] }}
            </span>
            @endif
        </div>
        <div class="pw-pipeline-link-note">
            @if ($pipelinePreview['unchanged'])
                Le statut pipeline reste inchangé après enregistrement de cet appel.
            @else
                Le statut pipeline du prospect passera automatiquement à « {{ $pipelinePreview['next']['label'] ?? '—' }} » lors de l'enregistrement.
            @endif
        </div>
    </div>
    @endif

    {{-- ── Rappel box ─────────────────────────────────────────────────── --}}
    <div id="pw-rappel-box"
        class="pw-rappel-box {{ $isRappelVisible ? 'visible' : '' }}">
        <div class="pw-rappel-box-title">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
            </svg>
            @if (in_array($selectedStatut, ['rapl_elu', 'rapl_std']))
            Créneau de rappel
            @else
            Planifier le rappel / RDV
            @endif
        </div>

        @if ($selectedStatut === 'rapl_elu')
        <div style="font-size:0.7rem; background:#fffbe6; border:1px dashed #d4a800; border-radius:0.5rem; padding:4px 8px; color:#7a5c00; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.375rem;">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            Note obligatoire dans le compte rendu : date + heure + nom de l'élu
        </div>
        @elseif ($selectedStatut === 'rapl_std')
        <div style="font-size:0.7rem; background:#fffbe6; border:1px dashed #d4a800; border-radius:0.5rem; padding:4px 8px; color:#7a5c00; margin-bottom:0.5rem; display:flex; align-items:center; gap:0.375rem;">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
            </svg>
            Note obligatoire dans le compte rendu : date + heure + nom du standard
        </div>
        @endif

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
            <div>
                <div class="pw-field-label">Date</div>
                <input type="date" wire:model="rappel_date" class="pw-field-input">
            </div>
            <div>
                <div class="pw-field-label">Heure</div>
                <input type="time" wire:model="rappel_heure" class="pw-field-input">
            </div>
        </div>
    </div>

    {{-- ── Commentaires ────────────────────────────────────────────────── --}}
    <textarea wire:model="commentaires" rows="4"
        placeholder="Compte rendu : interlocuteur joint, objections, décision, prochaine étape..."
        class="pw-textarea"></textarea>

    @if ($selectedStatut && in_array($selectedStatut, ['rapl_elu', 'rapl_std']) && !$commentaires)
    <div style="font-size:0.75rem; color:rgb(220 38 38); margin-top:0.5rem; display:flex; align-items:center; gap:0.375rem;">
        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
        </svg>
        Note obligatoire : date + heure + nom {{ $selectedStatut === 'rapl_elu' ? "de l'élu" : 'du standard' }}.
    </div>
    @elseif ($selectedStatut && !$commentaires && !in_array($selectedStatut, ['nrp', 'fax', 'maj']))
    <div style="font-size:0.75rem; color:rgb(249 115 22); margin-top:0.5rem; display:flex; align-items:center; gap:0.375rem;">
        <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
        </svg>
        Ajoutez un commentaire avant d'enregistrer.
    </div>
    @endif

    {{-- Erreurs de validation --}}
    @if ($errors->has('statut_resultat') || $errors->has('commentaires'))
    <div style="font-size:0.875rem; color:rgb(190 35 50); margin-top:0.75rem; background:rgb(254 226 226); border:1px solid rgb(248 113 113); border-radius:0.75rem; padding:0.75rem;">
        <div style="font-weight:700; margin-bottom:0.5rem;">Erreur de validation</div>
        @error('statut_resultat')
        <div>Statut : {{ $message }}</div>
        @enderror
        @error('commentaires')
        <div>Commentaires : {{ $message }}</div>
        @enderror
    </div>
    @endif

    @error('commentaires')
    <div style="font-size:0.75rem; color:rgb(190 35 50); margin-top:0.5rem;">
        {{ $message }}
    </div>
    @enderror

    {{-- ── Actions ─────────────────────────────────────────────────────── --}}
    <div class="pw-actions">
        <button wire:click="submitResult" wire:loading.attr="disabled" class="pw-btn-primary"
            {{ !$selectedStatut ? 'disabled style=opacity:.5;cursor:not-allowed' : '' }}>
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Enregistrer &amp; suivant
        </button>
        <button wire:click.prevent="skipCall" class="pw-btn-secondary" title="Repousser en fin de file">
            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
            </svg>
            Passer
        </button>
    </div>

    @if (!$selectedStatut || ($selectedStatut && $this->commentaireRequis() && !$commentaires))
    <div style="margin-top:0.75rem; color:rgb(190 35 50); font-size:.875rem;">
        @if (!$selectedStatut)
            Sélectionnez un statut dans le résultat d'appel pour activer l'enregistrement.
        @elseif ($this->commentaireRequis() && !$commentaires)
            Un commentaire est requis pour ce statut avant de pouvoir enregistrer.
        @endif
    </div>
    @endif

</div>
