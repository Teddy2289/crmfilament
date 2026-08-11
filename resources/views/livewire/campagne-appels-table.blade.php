@php
    $slug = \Illuminate\Support\Str::slug($statut);
    $wrapperId = 'campagne-appels-table-' . $slug;
@endphp

<div id="{{ $wrapperId }}" class="space-y-6">
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Filtres</h3>
            <p class="mt-1 text-xs text-slate-500">Affinez la liste des appels par téléprospecteur, agent, statut ou période.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Téléprospecteur</label>
                    <select wire:model="teleprospecteurId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Tous</option>
                        @foreach($this->teleprospecteurs as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Agent</label>
                    <select wire:model="agentId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Tous</option>
                        @foreach($this->agentOptions as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Statut</label>
                    <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Tous</option>
                        @foreach($this->statusOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Recherche</label>
                    <input wire:model.debounce.300ms="search" type="search" placeholder="Contact, téléphone ou recherche..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Date depuis</label>
                        <input wire:model="dateFrom" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Date jusqu'à</label>
                        <input wire:model="dateUntil" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    </div>
                </div>

                <div class="grid gap-2 pt-2 sm:grid-cols-[1fr_auto]">
                    <div class="flex items-center gap-2">
                        <button wire:click="resetFilters" type="button" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">Réinitialiser</button>
                        <button wire:click.prevent="downloadCsv" type="button" title="Téléchargement CSV avec séparateur ;" class="inline-flex items-center rounded-md border border-indigo-300 bg-indigo-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-indigo-700">Télécharger le CSV</button>
                    </div>
                    <span class="text-xs text-gray-500">{{ $appels->count() }} appel(s)</span>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-slate-900">Résumé</h3>
                    <p class="mt-1 text-xs text-slate-500">Vue rapide des appels pour ce statut.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.15em] text-slate-600">{{ $appels->count() }} résultat(s)</span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Statut</div>
                    <div class="mt-2 text-sm font-semibold text-slate-900">{{ $statut }}</div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Campagne</div>
                    <div class="mt-2 text-sm font-semibold text-slate-900">#{{ $campagneId }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table id="appels-table-{{ $slug }}" class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em] cursor-pointer select-none hover:bg-slate-100" data-sort="contact">
                            Contact <span class="sort-indicator ml-1 text-slate-400">↕</span>
                        </th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em] cursor-pointer select-none hover:bg-slate-100" data-sort="phone">
                            Téléphone <span class="sort-indicator ml-1 text-slate-400">↕</span>
                        </th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em] cursor-pointer select-none hover:bg-slate-100" data-sort="date">
                            Date <span class="sort-indicator ml-1 text-slate-400">↕</span>
                        </th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em] cursor-pointer select-none hover:bg-slate-100" data-sort="telepro">
                            Téléprospecteur <span class="sort-indicator ml-1 text-slate-400">↕</span>
                        </th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em] cursor-pointer select-none hover:bg-slate-100" data-sort="status">
                            Statut <span class="sort-indicator ml-1 text-slate-400">↕</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($appels as $appel)
                        <tr class="hover:bg-slate-50"
                            data-teleprospecteur="{{ $appel->appelable?->teleprospecteur_id ?? '' }}"
                            data-status="{{ strtolower($appel->phoning_status instanceof \BackedEnum ? $appel->phoning_status->value : (string) ($appel->phoning_status ?? '')) }}"
                            data-date="{{ optional($appel->date_heure)->format('Y-m-d') }}"
                            data-contact="{{ strtolower($appel->appelable?->nom ?? 'contact #' . $appel->appelable_id) }}"
                            data-phone="{{ strtolower($appel->appelable?->telephone ?? $appel->numero_appelant) }}"
                            data-telepro="{{ strtolower($appel->user?->nom ?? '') }}"
                        >
                            <td class="px-4 py-4">
                                <div class="font-medium text-slate-900">{{ $appel->appelable?->nom ?? 'Contact #' . $appel->appelable_id }}</div>
                                <div class="text-xs text-slate-500">{{ $appel->appelable?->ville ?? '—' }}</div>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $appel->appelable?->telephone ?? $appel->numero_appelant }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ optional($appel->date_heure)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $appel->user?->nom ?? '—' }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $this->getStatusBadgeClasses($appel->phoning_status) }}">{{ strtoupper($appel->phoning_status instanceof \BackedEnum ? $appel->phoning_status->value : ($appel->phoning_status ?? '-')) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Aucun appel pour ce statut.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-4 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-slate-500">Affichage de <span id="displayed-count-{{ $slug }}">{{ $appels->count() }}</span> sur {{ $appels->count() }} résultats</div>
            </div>
        </div>
    </div>


