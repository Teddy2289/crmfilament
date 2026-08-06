{{-- resources/views/filament/ns-conseil/pages/phoning-back-office.blade.php --}}
<x-filament-panels::page>

    @vite('resources/css/phoning-back-office.css')

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
        <script>
            // Livewire v3 : on initialise après chaque mise à jour du DOM
            let sortableInstance = null;

            function initSortable() {
                const list = document.getElementById('pbo-sortable-list');
                if (!list) return;

                if (sortableInstance) {
                    sortableInstance.destroy();
                    sortableInstance = null;
                }

                sortableInstance = new Sortable(list, {
                    animation: 150,
                    handle: '.pbo-col-grip',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                    onEnd: function() {
                        const ids = [...list.querySelectorAll('.pbo-row[data-id]')]
                            .map(el => parseInt(el.dataset.id));

                        const ind = document.getElementById('pbo-save-ind');
                        if (ind) {
                            ind.style.display = 'inline';
                            ind.style.animation = 'none';
                            void ind.offsetWidth;
                            ind.style.animation = '';
                            setTimeout(() => ind.style.display = 'none', 1200);
                        }

                        if (window.Livewire && ids.length > 0) {
                            const component = document.querySelector('[wire\\:id]')?.__livewire;
                            if (component && component.reorderFromDrag) {
                                component.reorderFromDrag(ids);
                            } else {
                                Livewire.dispatch('reorderFromDrag', { ids: ids });
                            }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', () => setTimeout(initSortable, 100));
            document.addEventListener('livewire:init',      () => setTimeout(initSortable, 100));
            document.addEventListener('livewire:navigated', () => setTimeout(initSortable, 100));

            function pboUpdateSelection() {
                const checked = [...document.querySelectorAll('.pbo-checkbox.row-check:checked')]
                    .map(c => parseInt(c.value));
                const bar = document.getElementById('pbo-bulk-bar');
                const cnt = document.getElementById('pbo-bulk-count');
                if (bar) bar.classList.toggle('visible', checked.length > 0);
                if (cnt) cnt.textContent = checked.length;
                @this.set('selectedIds', checked);
            }

            document.addEventListener('change', (e) => {
                if (!e.target.matches('.pbo-checkbox')) return;
                pboUpdateSelection();
            });

            function pboDeselectAll() {
                document.querySelectorAll('.pbo-checkbox.row-check').forEach(c => c.checked = false);
                const bar = document.getElementById('pbo-bulk-bar');
                if (bar) bar.classList.remove('visible');
                @this.set('selectedIds', []);
            }

            function pboSelectAll(el) {
                const checked = el.checked;
                document.querySelectorAll('.pbo-checkbox.row-check').forEach(c => c.checked = checked);
                pboUpdateSelection();
            }

            // Filtre client-side live
            document.addEventListener('input', (e) => {
                if (e.target.id !== 'pbo-search-input') return;
                const q = e.target.value.toLowerCase();
                document.querySelectorAll('.pbo-row[data-id]').forEach(row => {
                    const nom = (row.dataset.nom || '').toLowerCase();
                    const tel = (row.dataset.tel || '').toLowerCase();
                    row.style.display = (!q || nom.includes(q) || tel.includes(q)) ? '' : 'none';
                });
            });
        </script>
    @endpush

    @php
        $teleprospecteurs = $this->getTeleprospecteurs();
        $selectedUser     = $this->getSelectedUser();
        $nbTotal          = count($this->prospectList);
        $nbRetard         = collect($this->prospectList)->where('rappel_en_retard', true)->count();
    @endphp

    <div class="pbo">

        {{-- ══ TOPBAR ══ --}}
        <div class="pbo-topbar">
            <div class="pbo-user-select-wrap">
                <select wire:change="selectUser($event.target.value)" class="pbo-user-select">
                    @foreach ($teleprospecteurs as $user)
                        <option value="{{ $user['id'] }}" {{ $selectedUserId === $user['id'] ? 'selected' : '' }}>
                            {{ $user['nom_complet'] }} — {{ $user['nb_actifs'] }}
                            prospect{{ $user['nb_actifs'] > 1 ? 's' : '' }}
                        </option>
                    @endforeach
                    @if (empty($teleprospecteurs))
                        <option disabled>Aucun téléprospecteur actif</option>
                    @endif
                </select>
            </div>

            <div class="pbo-topbar-spacer"></div>

            <div class="pbo-pills">
                @if ($nbRetard > 0)
                    <div class="pbo-pill pbo-pill-danger">
                        <div class="pdot" style="background:#ef4444;"></div>
                        {{ $nbRetard }} retard{{ $nbRetard > 1 ? 's' : '' }}
                    </div>
                @endif
                @if ($nbTotal > 0)
                    <div class="pbo-pill pbo-pill-blue">
                        <div class="pdot" style="background:#3b82f6;"></div>
                        {{ $nbTotal }} en file
                    </div>
                @endif
                <span id="pbo-save-ind" class="pbo-saving" style="display:none;">✓ Sauvegardé</span>
                @if ($selectedUser && $nbTotal > 0)
                    <button wire:click="resetOrder"
                            onclick="return confirm('Réinitialiser l\'ordre par défaut ?')"
                            class="pbo-btn-reset">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Réinitialiser
                    </button>
                @endif
            </div>
        </div>

        {{-- ══ FILTRES ══ --}}
        <div class="pbo-filters">
            <div class="pbo-filters-head">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                </svg>
                Filtres
            </div>
            <div class="pbo-filters-row">
                <div class="pbo-filter-group">
                    <label>
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Recherche
                    </label>
                    <input id="pbo-search-input" type="text" placeholder="Nom ou téléphone…"
                        class="pbo-finput pbo-finput-search">
                </div>
                <div class="pbo-filter-group">
                    <label>Statut</label>
                    <select wire:model.live="filterStatut" class="pbo-fselect">
                        <option value="">Tous</option>
                        <option value="rpc">RPC</option>
                        <option value="rp">RP</option>
                        <option value="std_joint">STD-Joint</option>
                        <option value="ac">AC</option>
                        <option value="cse_nr">CSE-NR</option>
                        <option value="std_nr">STD-NR</option>
                    </select>
                </div>
                <div class="pbo-filter-group">
                    <label>
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        Département
                    </label>
                    <input wire:model.live="filterDept" type="text" placeholder="Ex : 75, 92…"
                        class="pbo-finput pbo-finput-dept">
                </div>
                <div class="pbo-filter-group" style="justify-content: flex-end;">
                    <label>&nbsp;</label>
                    <label style="flex-direction:row; align-items:center; gap:6px; height:34px; cursor:pointer; font-size:12.5px; font-weight:500; color:var(--pbo-text);">
                        <input wire:model.live="filterRappelOnly" type="checkbox" class="pbo-checkbox"
                            style="flex-shrink:0;">
                        Rappels seulement
                    </label>
                </div>
                <div class="pbo-filter-actions">
                    <button wire:click="applyFilters" class="pbo-btn-filter">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                        </svg>
                        Filtrer
                    </button>
                    <button wire:click="clearFilters" class="pbo-btn-clear" title="Réinitialiser les filtres">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ══ TABLEAU ══ --}}
        <x-phoning::queue-table
            :prospects="$prospectList"
            wire:model="selectedIds"
        />

    </div>{{-- /pbo --}}

</x-filament-panels::page>
