<x-filament-panels::page>
    <div style="padding: 1.25rem;">
        <div style="display:flex; align-items:flex-start; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700; margin:0 0 0.5rem;">Appels entrants</h1>
                <p style="color:rgb(55 65 81); margin:0; max-width:48rem; line-height:1.6;">
                    Interface d’appel entrant avec remontée automatique de la fiche CRM correspondante.
                    Lorsqu’un appel Ringover est détecté, la fiche du contact est proposée pour prise en charge.
                </p>
            </div>
        </div>

        @if ($incomingCallPhone || $incomingCallMatches)
            <div style="margin-bottom:1.25rem; background:rgb(239 246 255); border:1px solid rgb(191 219 254); border-radius:0.75rem; padding:1rem;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div>
                        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:rgb(30 64 175); font-weight:700;">Appel entrant détecté</div>
                        <div style="font-size:1.125rem; font-weight:700; color:rgb(30 41 59); margin-top:0.35rem;">{{ $incomingCallPhone ?? 'Numéro inconnu' }}</div>
                    </div>
                    @if ($incomingCallMatches)
                        <button type="button" wire:click='searchIncomingCallMatch("{{ $incomingCallPhone }}")' style="padding:0.75rem 1rem; border:none; border-radius:0.75rem; background:rgb(37 99 235); color:white; font-weight:600; cursor:pointer;">
                            Rechercher la fiche
                        </button>
                    @endif
                </div>

                @if ($incomingCallMatches)
                    <div style="margin-top:1rem; display:grid; gap:0.75rem;">
                        @foreach ($incomingCallMatches as $match)
                            <button type="button" wire:click="selectSearchResult({{ $match['id'] }}, '{{ $match['type'] }}')" style="text-align:left; padding:0.85rem 1rem; border:1px solid rgb(209 213 219); border-radius:0.75rem; background:white; cursor:pointer;">
                                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                                    <div>
                                        <div style="font-weight:700; color:rgb(17 24 39);">{{ $match['nom'] }}</div>
                                        <div style="font-size:0.85rem; color:rgb(75 85 99); margin-top:0.25rem;">{{ $match['type_entite'] }} · {{ $match['telephone'] ?? 'Téléphone inconnu' }}</div>
                                    </div>
                                    @if ($match['statut'])
                                        <span style="font-size:0.75rem; padding:0.25rem 0.6rem; border-radius:9999px; background:rgb(243 244 246); color:rgb(55 65 81); font-weight:600;">{{ $match['statut'] }}</span>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div style="margin-bottom:1.25rem; background:rgb(249 250 251); border:1px solid rgb(226 232 240); border-radius:0.75rem; padding:1rem; color:rgb(75 85 99);">
                Aucun appel entrant détecté pour le moment.
            </div>
        @endif

        @if ($currentContact)
            <div style="background:white; border:1px solid rgb(229 231 235); border-radius:0.75rem; padding:1rem; box-shadow:0 8px 20px rgba(15,23,42,0.05);">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
                    <div>
                        <div style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.08em; color:rgb(30 64 175); font-weight:700;">Fiche CRM associée</div>
                        <div style="font-size:1.25rem; font-weight:800; color:rgb(17 24 39); margin-top:0.35rem;">{{ $currentContactData['nom'] ?? 'Contact' }}</div>
                    </div>
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap; align-items:center;">
                        <span style="font-size:0.875rem; padding:0.35rem 0.75rem; border-radius:9999px; background:rgb(241 245 249); color:rgb(30 41 59);">{{ ucfirst($contactType) }}</span>
                        @if (! empty($currentContactData['statut']))
                            <span style="font-size:0.875rem; padding:0.35rem 0.75rem; border-radius:9999px; background:rgb(237 242 247); color:rgb(15 23 42);">{{ $currentContactData['statut'] }}</span>
                        @endif
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:1rem;">
                    <div style="background:rgb(249 250 251); border-radius:0.75rem; padding:1rem;">
                        <div style="font-size:0.75rem; color:rgb(71 85 105); font-weight:700; margin-bottom:0.5rem;">Téléphone</div>
                        <div style="font-size:1rem; color:rgb(15 23 42);">{{ $currentContactData['telephone'] ?? '—' }}</div>
                        @if (! empty($currentContactData['telephone_alt']))
                            <div style="font-size:0.875rem; color:rgb(75 85 99); margin-top:0.5rem;">Alternative : {{ $currentContactData['telephone_alt'] }}</div>
                        @endif
                    </div>
                    <div style="background:rgb(249 250 251); border-radius:0.75rem; padding:1rem;">
                        <div style="font-size:0.75rem; color:rgb(71 85 105); font-weight:700; margin-bottom:0.5rem;">Email</div>
                        <div style="font-size:1rem; color:rgb(15 23 42);">{{ $currentContactData['email'] ?? '—' }}</div>
                    </div>
                    <div style="background:rgb(249 250 251); border-radius:0.75rem; padding:1rem;">
                        <div style="font-size:0.75rem; color:rgb(71 85 105); font-weight:700; margin-bottom:0.5rem;">Adresse</div>
                        <div style="font-size:1rem; color:rgb(15 23 42);">{{ $currentContactData['adresse_complete'] ?? '—' }}</div>
                    </div>
                    <div style="background:rgb(249 250 251); border-radius:0.75rem; padding:1rem;">
                        <div style="font-size:0.75rem; color:rgb(71 85 105); font-weight:700; margin-bottom:0.5rem;">Autres</div>
                        <div style="font-size:0.95rem; color:rgb(15 23 42);">{{ $currentContactData['secteur_activite'] ?? $currentContactData['priorite'] ?? '—' }}</div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
