<x-filament-panels::page
    @class([
        'fi-resource-view-record-page',
        'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug()),
        'fi-resource-record-' . $record->getKey(),
    ])
>
    @php
        $stats = $record->getStats();
        $progression = min(100, max(0, (int) ($stats['progression'] ?? 0)));
        $statutLabel = $record->statutLabel($record->statut);
        $isActive = $record->statut === 'active';
    @endphp

    <div class="space-y-6">
        <x-filament::section>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span>Campagne de phoning</span>
                        <span class="text-gray-300 dark:text-gray-600">/</span>
                        <span>#{{ $record->id }}</span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">{{ $record->nom }}</h1>
                        <x-filament::badge :color="$isActive ? 'success' : ($record->statut === 'terminee' ? 'gray' : 'warning')">
                            {{ $statutLabel }}
                        </x-filament::badge>
                    </div>
                    <p class="mt-2 max-w-3xl text-sm text-gray-500 dark:text-gray-400">
                        Pilotez la campagne, identifiez immédiatement les priorités et lancez la file de phoning depuis un même écran.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button tag="a" href="{{ url('/ns-conseil/campagne-phonings') }}" color="gray" icon="heroicon-o-arrow-left">
                        Retour aux campagnes
                    </x-filament::button>
                    @if ($isActive)
                        <x-filament::button tag="a" href="{{ route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id]) }}" color="primary" icon="heroicon-o-phone">
                            Ouvrir le phoning
                        </x-filament::button>
                    @endif
                </div>
            </div>
        </x-filament::section>

        @php($diagnostic = $this->getDiagnosticVentilation())
        @php($prospectsReels = collect($diagnostic['cards'] ?? [])->firstWhere('key', 'total')['value'] ?? ($stats['total_contacts'] ?? 0))
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-5">
            @foreach ([
                ['Prospects réels', $prospectsReels, 'heroicon-o-users', 'gray'],
                ['Contacts traités', $stats['contacts_traites'] ?? 0, 'heroicon-o-check-circle', 'success'],
                ['Contacts uniques appelés', $stats['contacts_uniques_appeles'] ?? 0, 'heroicon-o-user-group', 'info'],
                ['Contacts restants', $stats['contacts_restants'] ?? 0, 'heroicon-o-clock', 'warning'],
                ['Appels passés', $stats['total_appels'] ?? 0, 'heroicon-o-phone', 'primary'],
            ] as [$label, $value, $icon, $color])
                <x-filament::section :icon="$icon" :icon-color="$color" compact>
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ number_format($value, 0, ',', ' ') }}</p>
                </x-filament::section>
            @endforeach
        </div>

        @if ($diagnostic['enabled'] ?? false)
            <x-filament::section heading="Diagnostic de la population" description="Chaque indicateur utilise le même périmètre que la campagne et les liens ouvrent la liste correspondante en lecture seule." icon="heroicon-o-chart-bar-square">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($diagnostic['cards'] as $card)
                        @php($active = $this->getActiveVentilation() === $card['key'])
                        <button type="button" wire:click="setVentilationFilter('{{ $card['key'] }}')" wire:loading.attr="disabled" class="w-full text-left block rounded-xl border p-4 transition {{ $active ? 'border-primary-500 bg-primary-50 dark:bg-primary-950/30' : 'border-gray-200 bg-white hover:border-primary-300 dark:border-white/10 dark:bg-white/5' }}">
                            <div class="flex items-start justify-between gap-3"><span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $card['label'] }}</span><x-filament::icon :icon="$card['icon']" class="h-5 w-5 text-{{ $card['color'] }}-600" /></div>
                            <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($card['value'], 0, ",", ' ') }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $card['description'] }}</p>
                        </button>
                    @endforeach
                </div>
                <div class="mt-5 border-t border-gray-200 pt-4 dark:border-white/10">
                    <div class="flex flex-wrap items-center justify-between gap-3"><h3 class="text-sm font-semibold text-gray-950 dark:text-white">Répartition exacte des statuts</h3><span class="text-xs text-gray-500">Département {{ $diagnostic['department'] ?? '—' }}</span></div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($diagnostic['statuses'] as $status => $count)
                            @php($statusFilter = 'status:'.$status)
                            <button type="button" wire:click="setVentilationFilter('{{ $statusFilter }}')" wire:loading.attr="disabled" class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-800 hover:bg-primary-100 dark:bg-white/10 dark:text-gray-100 dark:hover:bg-primary-950/40"><span>{{ $status }}</span><span class="rounded-md bg-white px-1.5 py-0.5 text-xs dark:bg-black/20">{{ number_format($count, 0, ',', ' ') }}</span></button>
                        @endforeach
                    </div>
                </div>
            </x-filament::section>
        @endif
        <div class="grid gap-6 xl:grid-cols-5">
            <div class="space-y-6 xl:col-span-3">
                <x-filament::section heading="Pilotage de la campagne" icon="heroicon-o-chart-bar-square">
                    <div class="space-y-5">
                        <div class="flex items-end justify-between gap-4">
                            <div>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Progression globale</p>
                                <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $progression }} <span class="text-base font-medium text-gray-500">%</span></p>
                            </div>
                            <p class="text-right text-sm text-gray-500 dark:text-gray-400">{{ $stats['contacts_traites'] ?? 0 }} traités<br>sur {{ $stats['total_contacts'] ?? 0 }}</p>
                        </div>
                        <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-600" style="width: {{ $progression }}%"></div>
                        </div>
                        <div class="grid gap-4 border-t border-gray-200 pt-4 dark:border-white/10 sm:grid-cols-3">
                            <div><p class="text-xs uppercase tracking-wide text-gray-500">Début</p><p class="mt-1 font-medium text-gray-950 dark:text-white">{{ $record->date_debut?->format('d/m/Y') ?? 'Non défini' }}</p></div>
                            <div><p class="text-xs uppercase tracking-wide text-gray-500">Fin</p><p class="mt-1 font-medium text-gray-950 dark:text-white">{{ $record->date_fin?->format('d/m/Y') ?? 'Non définie' }}</p></div>
                            <div><p class="text-xs uppercase tracking-wide text-gray-500">Cible</p><p class="mt-1 font-medium text-gray-950 dark:text-white">{{ $record->type_entite_label ?? $record->type_entite ?? '—' }}</p></div>
                        </div>
                    </div>
                </x-filament::section>

                <x-filament::section heading="Informations de campagne" icon="heroicon-o-information-circle">
                    {{ $this->infolist }}
                </x-filament::section>
            </div>

            <div class="space-y-6 xl:col-span-2">
                <x-filament::section heading="Actions rapides" icon="heroicon-o-bolt">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                        @if ($isActive)
                            <x-filament::button tag="a" href="{{ route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id]) }}" color="primary" icon="heroicon-o-phone-arrow-up-right" class="w-full justify-center">
                                Lancer la file priorisée
                            </x-filament::button>
                        @endif
                        <x-filament::button tag="a" href="{{ url('/ns-conseil/campagne-phonings/' . $record->id . '/edit') }}" color="gray" icon="heroicon-o-pencil-square" class="w-full justify-center">
                            Modifier la campagne
                        </x-filament::button>
                    </div>
                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Lecture rapide</p>
                        <dl class="mt-3 space-y-3 text-sm">
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Statut</dt><dd class="font-medium text-gray-950 dark:text-white">{{ $statutLabel }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Contacts restants</dt><dd class="font-semibold text-warning-600">{{ number_format($stats['contacts_restants'] ?? 0, 0, ',', ' ') }}</dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-gray-500">Dernière mise à jour</dt><dd class="font-medium text-gray-950 dark:text-white">{{ $record->updated_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                        </dl>
                    </div>
                </x-filament::section>
            </div>
        </div>

        <x-filament::section heading="File de phoning" description="Les contacts prioritaires sont affichés ici. Utilisez la recherche, les filtres et la pagination pour travailler par lot." icon="heroicon-o-queue-list">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-primary-200 bg-primary-50 p-3 dark:border-primary-500/30 dark:bg-primary-950/20">
                <div>
                    <p class="text-sm font-semibold text-primary-900 dark:text-primary-100">Filtre actif : file d’attente</p>
                    <p class="text-xs text-primary-700 dark:text-primary-300">Affiche uniquement les contacts actuellement disponibles pour le phoning.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button
                        tag="a"
                        href="{{ request()->fullUrlWithQuery(['ventilation' => 'available']) }}"
                        color="primary"
                        size="sm"
                        icon="heroicon-o-queue-list"
                    >
                        File d’attente
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        href="{{ request()->fullUrlWithQuery(['ventilation' => 'targeted']) }}"
                        color="gray"
                        size="sm"
                        icon="heroicon-o-users"
                    >
                        Tous les ciblés
                    </x-filament::button>
                    <x-filament::button
                        tag="a"
                        href="{{ request()->fullUrlWithQuery(['ventilation' => 'multi_appels']) }}"
                        color="warning"
                        size="sm"
                        icon="heroicon-o-arrow-path"
                    >
                        Multi-appelés
                    </x-filament::button>
                </div>
            </div>
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament-panels::page>
