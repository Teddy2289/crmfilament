@php
    $slug = \Illuminate\Support\Str::slug($statut);
    $wrapperId = 'campagne-appels-table-' . $slug;
@endphp

<div id="{{ $wrapperId }}" class="space-y-6">
    <div class="grid gap-4 xl:grid-cols-3">
        <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Filtres</h3>
            <p class="mt-1 text-xs text-slate-500">Affinez la liste des appels par téléprospecteur, date ou recherche.</p>

            <div class="mt-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-700">Téléprospecteur</label>
                    <select id="filter-teleprospecteur-{{ $slug }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        <option value="">Tous</option>
                        @foreach($this->teleprospecteurs as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700">Recherche</label>
                    <input id="filter-search-{{ $slug }}" type="search" placeholder="Contact, téléphone ou téléprospecteur" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Date depuis</label>
                        <input id="filter-date-from-{{ $slug }}" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700">Date jusqu'à</label>
                        <input id="filter-date-until-{{ $slug }}" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                    </div>
                </div>

                <div class="grid gap-2 pt-2 sm:grid-cols-[1fr_auto]">
                    <div class="flex items-center gap-2">
                        <button id="reset-filters-{{ $slug }}" type="button" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">Réinitialiser</button>
                        <button id="export-appels-{{ $slug }}" type="button" title="Téléchargement CSV avec séparateur ;" class="inline-flex items-center rounded-md border border-indigo-300 bg-indigo-600 px-3 py-2 text-xs font-medium text-white shadow-sm hover:bg-indigo-700">Télécharger le CSV</button>
                    </div>
                    <span class="text-xs text-gray-500"><span id="filtered-count-{{ $slug }}">{{ $appels->count() }}</span> appel(s)</span>
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
                    <tr id="no-results-row-{{ $slug }}" class="hidden">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Aucune ligne ne correspond aux filtres sélectionnés.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-200 px-4 py-4 sm:px-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-slate-500">Affichage de <span id="displayed-count-{{ $slug }}">{{ $appels->count() }}</span> sur {{ $appels->count() }} résultats</div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('{{ $wrapperId }}');
            if (! container) {
                return;
            }

            const filters = {
                teleprospecteur: container.querySelector('#filter-teleprospecteur-{{ $slug }}'),
                dateFrom: container.querySelector('#filter-date-from-{{ $slug }}'),
                dateUntil: container.querySelector('#filter-date-until-{{ $slug }}'),
                search: container.querySelector('#filter-search-{{ $slug }}'),
            };
            const table = container.querySelector('#appels-table-{{ $slug }}');
            const rows = Array.from(table.querySelectorAll('tbody tr[data-contact]'));
            const filteredCount = container.querySelector('#filtered-count-{{ $slug }}');
            const displayedCount = container.querySelector('#displayed-count-{{ $slug }}');
            const noResultsRow = container.querySelector('#no-results-row-{{ $slug }}');
            const exportButton = container.querySelector('#export-appels-{{ $slug }}');
            const resetButton = container.querySelector('#reset-filters-{{ $slug }}');

            const normalize = (value) => String(value || '').trim().toLowerCase();
            // Parse date-only strings as local dates (avoid timezone/UTC inconsistencies)
            const parseDate = (value) => value ? new Date(value + 'T00:00') : null;
            let sortKey = null;
            let sortDirection = 'asc';

            const updateCounts = (visibleRows) => {
                if (filteredCount) {
                    filteredCount.textContent = `${visibleRows}`;
                }
                if (displayedCount) {
                    displayedCount.textContent = `${visibleRows}`;
                }
            };

            const compareRowValues = (a, b, key) => {
                if (key === 'date') {
                    const aDate = parseDate(a.dataset.date);
                    const bDate = parseDate(b.dataset.date);
                    if (!aDate && !bDate) return 0;
                    if (!aDate) return -1;
                    if (!bDate) return 1;
                    return aDate - bDate;
                }

                const left = normalize(a.dataset[key]);
                const right = normalize(b.dataset[key]);

                if (left < right) return -1;
                if (left > right) return 1;
                return 0;
            };

            const sortVisibleRows = () => {
                if (!sortKey) {
                    return;
                }

                const visibleRows = rows
                    .filter((row) => row.style.display !== 'none')
                    .sort((a, b) => {
                        const diff = compareRowValues(a, b, sortKey);
                        return sortDirection === 'asc' ? diff : -diff;
                    });

                const tbody = table.querySelector('tbody');
                if (!tbody) {
                    return;
                }

                visibleRows.forEach((row) => tbody.appendChild(row));
            };

            const updateSortIndicators = () => {
                const headerCells = container.querySelectorAll('th[data-sort]');

                headerCells.forEach((header) => {
                    const indicator = header.querySelector('.sort-indicator');
                    const key = header.dataset.sort;

                    if (!indicator) {
                        return;
                    }

                    if (key === sortKey) {
                        indicator.textContent = sortDirection === 'asc' ? '↑' : '↓';
                        header.classList.add('text-slate-900');
                    } else {
                        indicator.textContent = '↕';
                        header.classList.remove('text-slate-900');
                    }
                });
            };

            const setSortKey = (key) => {
                if (sortKey === key) {
                    sortDirection = sortDirection === 'asc' ? 'desc' : 'asc';
                } else {
                    sortKey = key;
                    sortDirection = 'asc';
                }

                updateSortIndicators();
                applyFilters();
            };

            const headerCells = container.querySelectorAll('th[data-sort]');
            headerCells.forEach((header) => {
                header.addEventListener('click', () => {
                    const key = header.dataset.sort;
                    if (key) {
                        setSortKey(key);
                    }
                });
            });

            const applyFilters = () => {
                const teleprospecteur = normalize(filters.teleprospecteur?.value);
                const dateFromRaw = parseDate(filters.dateFrom?.value);
                const dateUntilRaw = parseDate(filters.dateUntil?.value);

                // Make dateFrom start-of-day and dateUntil end-of-day to be inclusive
                const dateFrom = dateFromRaw ? new Date(dateFromRaw.getFullYear(), dateFromRaw.getMonth(), dateFromRaw.getDate(), 0, 0, 0, 0) : null;
                const dateUntil = dateUntilRaw ? new Date(dateUntilRaw.getFullYear(), dateUntilRaw.getMonth(), dateUntilRaw.getDate(), 23, 59, 59, 999) : null;
                const search = normalize(filters.search?.value);

                let visible = 0;

                rows.forEach((row) => {
                    const rowTeleprospecteur = normalize(row.dataset.teleprospecteur);
                    const rowDate = parseDate(row.dataset.date);
                    const rowContact = normalize(row.dataset.contact);
                    const rowPhone = normalize(row.dataset.phone);
                    const rowTelepro = normalize(row.dataset.telepro);

                    const matchesTeleprospecteur = !teleprospecteur || rowTeleprospecteur === teleprospecteur;
                    const matchesDateFrom = !dateFrom || (rowDate && rowDate >= dateFrom);
                    const matchesDateUntil = !dateUntil || (rowDate && rowDate <= dateUntil);
                    const matchesSearch = !search || rowContact.includes(search) || rowPhone.includes(search) || rowTelepro.includes(search);

                    const visibleRow = matchesTeleprospecteur && matchesDateFrom && matchesDateUntil && matchesSearch;
                    row.style.display = visibleRow ? '' : 'none';

                    if (visibleRow) {
                        visible += 1;
                    }
                });

                sortVisibleRows();

                if (noResultsRow) {
                    noResultsRow.classList.toggle('hidden', visible > 0);
                }
                updateCounts(visible);
            };

            const resetFilters = () => {
                Object.values(filters).forEach((element) => {
                    if (element) {
                        element.value = '';
                    }
                });
                applyFilters();
            };

            const buildCsv = () => {
                const visibleRows = rows.filter((row) => row.style.display !== 'none');
                const headers = ['Contact', 'Téléphone', 'Date', 'Téléprospecteur', 'Statut'];
                const csvRows = [headers.join(';')];

                visibleRows.forEach((row) => {
                    const cells = Array.from(row.querySelectorAll('td')).map((cell) => {
                        const text = cell.textContent.replace(/\s+/g, ' ').trim();
                        return `"${text.replace(/"/g, '""')}"`;
                    });
                    csvRows.push(cells.join(';'));
                });

                return csvRows.join('\n');
            };

            const downloadCsv = () => {
                const csv = buildCsv();
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                    link.setAttribute('download', `appels-{{ $campagneId }}-{{ $slug }}.csv`);
                    link.style.display = 'none';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                };

                Object.values(filters).forEach((element) => {
                    if (element) {
                        element.addEventListener('change', applyFilters);
                        element.addEventListener('input', applyFilters);
                    }
                });

                if (resetButton) {
                    resetButton.addEventListener('click', resetFilters);
                }

                if (exportButton) {
                    exportButton.addEventListener('click', downloadCsv);
                }

                applyFilters();
            });
        </script>
