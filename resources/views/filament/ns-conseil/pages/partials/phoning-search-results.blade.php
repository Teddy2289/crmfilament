        @if ($showSearchResults && count($searchResults) > 0)
        <div style="position:absolute; z-index:1000; background:white; border:1px solid rgb(229 231 235); border-radius:0.75rem; box-shadow:0 10px 25px rgba(0,0,0,0.15); max-height:400px; overflow-y:auto; width:100%; max-width:600px; margin-top:0.25rem;">
            @foreach ($searchResults as $result)
            <div wire:click="selectSearchResult({{ $result['id'] }}, '{{ $result['type'] }}')"
                style="display:flex; align-items:center; justify-content:space-between; padding:0.625rem 1rem; cursor:pointer; border-bottom:1px solid rgb(243 244 246); transition:background 0.15s;"
                onmouseover="this.style.background='rgb(249 250 251)'" onmouseout="this.style.background='white'">
                <div style="flex:1; min-width:0;">
                    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                        <span style="font-weight:600; font-size:0.875rem; color:rgb(17 24 39);">{{ $result['nom'] }}</span>
                        <span style="font-size:0.625rem; padding:0.125rem 0.375rem; border-radius:9999px; background:rgb(219 234 254); color:rgb(30 64 175); font-weight:600;">
                            {{ $result['type_entite'] }}
                        </span>
                        @if ($result['statut'])
                        <span style="font-size:0.625rem; padding:0.125rem 0.375rem; border-radius:9999px; background:rgb(243 244 246); color:rgb(55 65 81);">
                            {{ $result['statut'] }}
                        </span>
                        @endif
                    </div>
                    <div style="display:flex; align-items:center; gap:0.75rem; font-size:0.75rem; color:rgb(107 114 128); margin-top:0.125rem; flex-wrap:wrap;">
                        @if ($result['telephone'])
                        <span style="display:inline-flex; align-items:center; gap:0.25rem;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            {{ $result['telephone'] }}
                        </span>
                        @endif
                        @if ($result['ville'])
                        <span style="display:inline-flex; align-items:center; gap:0.25rem;">
                            <svg class="pw-icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            {{ $result['ville'] }}
                        </span>
                        @endif
                    </div>
                </div>
                <button onclick="event.stopPropagation(); appelerAvecRingover('{{ $result['telephone'] }}')" style="padding:0.25rem 0.75rem; background:rgb(34 197 94); color:white; border:none; border-radius:0.5rem; font-size:0.6875rem; font-weight:600; cursor:pointer; white-space:nowrap; flex-shrink:0;">
                    Appeler →
                </button>
            </div>
            @endforeach
        </div>
        @elseif ($showSearchResults && strlen($searchQuery) >= 2)
        <div style="position:absolute; z-index:1000; background:white; border:1px solid rgb(229 231 235); border-radius:0.75rem; box-shadow:0 10px 25px rgba(0,0,0,0.15); padding:1rem; text-align:center; color:rgb(107 114 128); width:100%; max-width:600px; margin-top:0.25rem;">
            <svg style="width:2rem;height:2rem;margin:0 auto 0.5rem;color:rgb(203 213 225);" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <div style="font-size:0.875rem;">Aucun contact trouvé pour "{{ $searchQuery }}"</div>
        </div>
        @endif