<div id="resultats-appels" class="space-y-5">
    @php
        $campagne = $this->campagne;
        $statusCounts = $this->statusCounts;
    @endphp
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm text-gray-500 dark:text-gray-400">Vue analytique de la campagne</p>
            <h3 class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">Résultats des appels</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choisissez un statut pour afficher ses appels, puis affinez la liste.</p>
        </div>
        <a href="{{ route('ns-conseil.campagnes.export-csv', ['campagne' => $campagneId]) . '?' . http_build_query($exportQuery ?? []) }}" class="fi-btn fi-btn-size-sm fi-color-gray inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />Exporter le statut</a>
    </div>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl bg-gray-50 p-3 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10"><p class="text-xs text-gray-500 dark:text-gray-400">Total appels</p><p class="mt-1 text-xl font-bold text-gray-950 dark:text-white">{{ $this->totalAppels }}</p></div>
        <div class="rounded-xl bg-primary-50 p-3 ring-1 ring-primary-600/10 dark:bg-primary-950/30"><p class="text-xs text-primary-700 dark:text-primary-300">Statuts actifs</p><p class="mt-1 text-xl font-bold text-primary-800 dark:text-primary-200">{{ count($this->statuts) }}</p></div>
        <div class="rounded-xl bg-success-50 p-3 ring-1 ring-success-600/10 dark:bg-success-950/30"><p class="text-xs text-success-700 dark:text-success-300">Statut sélectionné</p><p class="mt-1 truncate text-xl font-bold text-success-800 dark:text-success-200">{{ $statusCounts[$this->activeStatut] ?? 0 }}</p></div>
        <div class="rounded-xl bg-warning-50 p-3 ring-1 ring-warning-600/10 dark:bg-warning-950/30"><p class="text-xs text-warning-700 dark:text-warning-300">Affichés</p><p class="mt-1 text-xl font-bold text-warning-800 dark:text-warning-200">{{ $appels->count() }}</p></div>
    </div>

    <section class="mb-4 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="text-sm font-semibold text-gray-950 dark:text-white">Répartition par statut</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Contacts uniques dans le périmètre de dates sélectionné.</p>
            </div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $this->totalAppels }} appel(s) au total</span>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->statuts as $code)
                <a href="{{ request()->fullUrlWithQuery(['resultats_statut' => $code]) }}#resultats-appels" class="rounded-lg bg-gray-50 p-3 ring-1 ring-gray-950/5 transition hover:bg-gray-100 dark:bg-white/5 dark:ring-white/10 dark:hover:bg-white/10">
                    <div class="flex items-start justify-between gap-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $campagne?->statutLabel($code) ?? strtoupper($code) }}</span>
                        <x-filament::icon icon="heroicon-o-user-group" class="h-4 w-4 text-primary-600" />
                    </div>
                    <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $this->contactsByStatus[$code] ?? 0 }}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">contact(s)</p>
                </a>
            @endforeach
        </div>
    </section>
    @php
        $tableFilters = request()->query('tableFilters', []);
        $periode = is_array($tableFilters) && is_array($tableFilters['periode'] ?? null) ? $tableFilters['periode'] : [];
        $exportQuery = array_filter([
            'statut' => $this->activeStatut,
            'dateFrom' => $this->dateFrom,
            'dateUntil' => $this->dateUntil,
            'teleprospecteurId' => $this->teleprospecteurId,
            'search' => $this->search,
        ], fn ($value) => $value !== null && $value !== '');
        $resetQuery = array_filter([
            'resultats_statut' => $this->activeStatut,
            'tableFilters' => $tableFilters,
        ], fn ($value) => $value !== null && $value !== []);
    @endphp
    <form method="GET" action="{{ request()->url() }}" class="grid gap-3 rounded-xl bg-gray-50 p-4 ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 md:grid-cols-2 xl:grid-cols-5">
        <input type="hidden" name="resultats_statut" value="{{ $this->activeStatut }}" />
        <input type="hidden" name="tableFilters[periode][date_debut]" value="{{ $periode['date_debut'] ?? '' }}" />
        <input type="hidden" name="tableFilters[periode][date_fin]" value="{{ $periode['date_fin'] ?? '' }}" />
        <div class="xl:col-span-2"><label class="text-xs font-medium text-gray-600 dark:text-gray-300">Recherche</label><input name="search" value="{{ $this->search }}" type="search" placeholder="Contact ou téléphone" class="fi-input mt-1 block w-full" /></div>
        <div><label class="text-xs font-medium text-gray-600 dark:text-gray-300">Téléprospecteur</label><select name="teleprospecteurId" class="fi-select mt-1 block w-full"><option value="">Tous</option>@foreach($this->teleprospecteurs as $id => $name)<option value="{{ $id }}" @selected((string) $this->teleprospecteurId === (string) $id)>{{ $name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-gray-600 dark:text-gray-300">Depuis</label><input name="dateFrom" value="{{ $this->dateFrom }}" type="date" class="fi-input mt-1 block w-full" /></div>
        <div><label class="text-xs font-medium text-gray-600 dark:text-gray-300">Jusqu’au</label><input name="dateUntil" value="{{ $this->dateUntil }}" type="date" class="fi-input mt-1 block w-full" /></div>
        <div class="flex flex-wrap gap-2 md:col-span-2 xl:col-span-5">
            <button type="submit" class="fi-btn fi-btn-size-sm fi-color-primary inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-funnel" class="h-4 w-4" />Appliquer les filtres</button>
            <a href="{{ request()->url() . '?' . http_build_query($resetQuery) }}#resultats-appels" class="fi-btn fi-btn-size-sm fi-color-gray inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-arrow-path" class="h-4 w-4" />Réinitialiser</a>
            <a href="{{ route('ns-conseil.campagnes.export-csv', ['campagne' => $campagneId]) . '?' . http_build_query($exportQuery) }}" class="fi-btn fi-btn-size-sm fi-color-primary inline-flex items-center gap-2"><x-filament::icon icon="heroicon-o-arrow-down-tray" class="h-4 w-4" />Télécharger le CSV</a>
        </div>
    </form>
    <div class="flex gap-2 overflow-x-auto border-b border-gray-200 pb-2 dark:border-white/10">
        @foreach ($this->statuts as $code)
            <a href="{{ request()->fullUrlWithQuery(["resultats_statut" => $code]) }}#resultats-appels" class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium {{ $this->activeStatut === $code ? "bg-primary-600 text-white" : "bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-200" }}">
                <span>{{ $campagne?->statutLabel($code) ?? $code }}</span><span class="rounded-md bg-black/10 px-1.5 py-0.5 text-xs">{{ $statusCounts[$code] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        <div class="flex items-center justify-between bg-gray-50 px-4 py-3 dark:bg-white/5"><div><p class="font-semibold text-gray-950 dark:text-white">{{ $campagne?->statutLabel($this->activeStatut) ?? $this->activeStatut }}</p><p class="text-xs text-gray-500 dark:text-gray-400">{{ $appels->count() }} résultat(s) après filtrage</p></div><span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ strtoupper($this->activeStatut) }}</span></div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:text-gray-400 dark:bg-white/5 dark:text-gray-400"><tr><th class="px-4 py-3">Contact</th><th class="px-4 py-3">Téléphone</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Téléprospecteur</th><th class="px-4 py-3">Statut</th></tr></thead><tbody class="divide-y divide-gray-200 bg-white dark:divide-white/10 dark:bg-gray-900">
            @forelse($appels as $appel)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5"><td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $appel->appelable?->nom ?? 'Contact #' . $appel->appelable_id }}<span class="block text-xs font-normal text-gray-500 dark:text-gray-400">{{ $appel->appelable?->ville ?? '' }}</span></td><td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ $appel->appelable?->telephone ?? $appel->numero_appelant ?? '—' }}</td><td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">{{ optional($appel->date_heure)->format('d/m/Y H:i') }}</td><td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ trim(($appel->user?->prenom ?? '') . ' ' . ($appel->user?->nom ?? '')) ?: '—' }}</td><td class="px-4 py-3"><x-filament::badge color="gray">{{ strtoupper((string) $appel->phoning_status) }}</x-filament::badge></td></tr>
            @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">Aucun appel ne correspond aux filtres sélectionnés.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
</div>
