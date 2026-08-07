@props(['phone', 'nrCount', 'maxNr', 'callId', 'incomingCallPhone' => null, 'incomingCallMatches' => []])

{{--
    Composant : ringover-widget
    Contient :
      - Boîte NR (sans réponse) avec compteur de tentatives et barre de progression
      - Iframe Ringover (embedé via SDK, monté via JS)
      - Bandeau d'appel entrant détecté (incomingCallPhone / incomingCallMatches)

    Props :
      - phone      : string|null  — numéro du contact courant (pour appel sortant)
      - nrCount    : int          — nombre de tentatives NR actuelles
      - maxNr      : int          — maximum de tentatives NR configuré
      - callId     : string|null  — identifiant de l'appel Ringover courant

    Variables Livewire attendues dans le contexte parent :
      - $incomingCallPhone   : string|null
      - $incomingCallMatches : array
--}}

{{-- ── Boîte NR (Sans réponse) ─────────────────────────────────────────── --}}
<div class="pw-nr-box">
    <div class="pw-nr-box-header">
        <span class="pw-nr-title">Sans réponse</span>
        <span class="pw-nr-subtitle">08:00 – 18:00</span>
    </div>
    <div class="pw-nr-box-metrics">
        <span class="pw-nr-count">{{ $nrCount }}</span>
        <span class="pw-nr-tentatives">/ {{ $maxNr }} tentatives</span>
    </div>
    <div class="pw-nr-progress">
        @for ($i = 0; $i < $maxNr; $i++)
            <div style="flex:1; height:0.25rem; border-radius:9999px; background:{{ $i < $nrCount ? 'rgb(249 115 22)' : 'rgb(229 231 235)' }};"></div>
        @endfor
    </div>
</div>

{{-- ── Widget Ringover (iframe embedé via SDK) ─────────────────────────── --}}
<div
    wire:ignore
    id="ringover-embed-phoning"
    class="pw-ringover-embed"
></div>

{{-- ── Appel entrant détecté ───────────────────────────────────────────── --}}
@if ($incomingCallPhone || $incomingCallMatches)
<div class="pw-incoming-call-box">
    <div class="pw-incoming-call-header">
        <div>
            <div class="pw-incoming-call-label">Appel entrant détecté</div>
            <div class="pw-incoming-call-title">{{ $incomingCallPhone ?? 'Numéro inconnu' }}</div>
        </div>
        @if ($incomingCallMatches)
        <button
            type="button"
            onclick="rechercherAppelEntrant('{{ $incomingCallPhone }}')"
            class="pw-incoming-call-button"
        >
            Rechercher la fiche
        </button>
        @endif
    </div>

    @if ($incomingCallMatches)
    <div class="pw-incoming-call-matches">
        @foreach ($incomingCallMatches as $match)
        <button
            type="button"
            wire:click="selectSearchResult({{ $match['id'] }}, '{{ $match['type'] }}')"
            class="pw-incoming-match"
        >
            <div class="pw-incoming-match-row">
                <div>
                    <div class="pw-incoming-match-name">{{ $match['nom'] }}</div>
                    <div class="pw-incoming-match-meta">{{ $match['type_entite'] }} · {{ $match['telephone'] ?? '—' }}</div>
                </div>
                @if ($match['statut'])
                <span class="pw-incoming-match-badge">{{ $match['statut'] }}</span>
                @endif
            </div>
        </button>
        @endforeach
    </div>
    @endif
</div>
@endif
