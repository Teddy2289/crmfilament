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
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
        <span class="pw-nr-title">Sans réponse</span>
        <span class="pw-nr-subtitle">08:00 – 18:00</span>
    </div>
    <div style="display:flex; align-items:baseline; gap:0.375rem; margin-bottom:0.5rem;">
        <span class="pw-nr-count">{{ $nrCount }}</span>
        <span class="pw-nr-tentatives">/ {{ $maxNr }} tentatives</span>
    </div>
    <div style="display:flex; gap:0.25rem;">
        @for ($i = 0; $i < $maxNr; $i++)
            <div style="flex:1; height:0.25rem; border-radius:9999px; background:{{ $i < $nrCount ? 'rgb(249 115 22)' : 'rgb(229 231 235)' }};"></div>
        @endfor
    </div>
</div>

{{-- ── Widget Ringover (iframe embedé via SDK) ─────────────────────────── --}}
<div
    wire:ignore
    id="ringover-embed-phoning"
    style="width:100%; max-width:100%; height:560px; border-radius:0.75rem; overflow:hidden; box-sizing:border-box; border:1px solid rgb(229 231 235); margin-bottom:1rem;"
></div>

{{-- ── Appel entrant détecté ───────────────────────────────────────────── --}}
@if ($incomingCallPhone || $incomingCallMatches)
<div style="margin:1rem 0 0; background:rgb(239 246 255); border:1px solid rgb(191 219 254); border-radius:0.75rem; padding:0.875rem 1rem;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
        <div>
            <div style="font-size:0.625rem; text-transform:uppercase; letter-spacing:0.08em; color:rgb(30 64 175); font-weight:700;">Appel entrant détecté</div>
            <div style="font-size:1rem; font-weight:700; color:rgb(30 41 59); margin-top:0.25rem;">{{ $incomingCallPhone ?? 'Numéro inconnu' }}</div>
        </div>
        @if ($incomingCallMatches)
        <button
            type="button"
            onclick="rechercherAppelEntrant('{{ $incomingCallPhone }}')"
            style="padding:0.5rem 0.875rem; border:none; border-radius:0.5rem; background:rgb(37 99 235); color:white; font-weight:600; cursor:pointer;"
        >
            Rechercher la fiche
        </button>
        @endif
    </div>

    @if ($incomingCallMatches)
    <div style="margin-top:0.75rem; display:flex; flex-direction:column; gap:0.5rem;">
        @foreach ($incomingCallMatches as $match)
        <button
            type="button"
            wire:click="selectSearchResult({{ $match['id'] }}, '{{ $match['type'] }}')"
            style="text-align:left; padding:0.625rem 0.75rem; border:1px solid rgb(191 219 254); border-radius:0.625rem; background:white; cursor:pointer;"
        >
            <div style="display:flex; align-items:center; justify-content:space-between; gap:0.75rem; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700; color:rgb(17 24 39);">{{ $match['nom'] }}</div>
                    <div style="font-size:0.75rem; color:rgb(75 85 99); margin-top:0.125rem;">{{ $match['type_entite'] }} · {{ $match['telephone'] ?? '—' }}</div>
                </div>
                @if ($match['statut'])
                <span style="font-size:0.625rem; padding:0.15rem 0.45rem; border-radius:9999px; background:rgb(243 244 246); color:rgb(55 65 81); font-weight:600;">{{ $match['statut'] }}</span>
                @endif
            </div>
        </button>
        @endforeach
    </div>
    @endif
</div>
@endif
