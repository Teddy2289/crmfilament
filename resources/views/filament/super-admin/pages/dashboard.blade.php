<x-filament-panels::page>
@php
    $stats = $this->getStats();
    $roles = $this->getRolesDistribution();
    $recent = $this->getRecentUsers();
    $roleTotal = $roles->sum('users_count') ?: 1;
    $roleMax = $roles->max('users_count') ?: 1;

    $kpis = [
        ['label' => 'Utilisateurs', 'value' => $stats['users'], 'sub' => $stats['actifs'].' actifs', 'href' => '/super-admin/users', 'color' => 'indigo'],
        ['label' => 'Rôles', 'value' => $stats['roles'], 'sub' => $stats['permissions'].' permissions', 'href' => '/super-admin/roles', 'color' => 'purple'],
        ['label' => 'Tables BDD', 'value' => $stats['tables'], 'sub' => $stats['db_size'], 'href' => '/super-admin/database-manager', 'color' => 'emerald'],
        ['label' => 'Imports', 'value' => $stats['imports'], 'sub' => "logs d'import", 'href' => '/super-admin/import-logs', 'color' => 'amber'],
        ['label' => 'Permissions', 'value' => $stats['permissions'], 'sub' => 'droits disponibles', 'href' => '/super-admin/roles', 'color' => 'rose'],
    ];

    $links = [
        ['href' => '/super-admin/users', 'icon' => 'heroicon-o-users', 'label' => 'Utilisateurs', 'meta' => 'Comptes, rôles et statuts'],
        ['href' => '/super-admin/roles', 'icon' => 'heroicon-o-shield-check', 'label' => 'Rôles', 'meta' => 'Permissions applicatives'],
        ['href' => '/super-admin/crm-profiles', 'icon' => 'heroicon-o-adjustments-horizontal', 'label' => 'Profils CRM', 'meta' => 'Capacités par profil'],
        ['href' => '/super-admin/crm-settings', 'icon' => 'heroicon-o-cog-6-tooth', 'label' => 'Paramètres CRM', 'meta' => 'Dictionnaires et réglages'],
        ['href' => '/super-admin/pipeline-statuts', 'icon' => 'heroicon-o-bars-3-bottom-left', 'label' => 'Statuts pipeline', 'meta' => 'Étapes commerciales'],
        ['href' => '/super-admin/workflow-groupes', 'icon' => 'heroicon-o-arrow-path-rounded-square', 'label' => 'Workflows', 'meta' => 'Groupes et automatisations'],
        ['href' => '/super-admin/database-manager', 'icon' => 'heroicon-o-circle-stack', 'label' => 'Base de données', 'meta' => 'Inspection technique'],
        ['href' => '/super-admin/import-logs', 'icon' => 'heroicon-o-arrow-up-tray', 'label' => 'Logs imports', 'meta' => 'Historique des traitements'],
    ];

    $documentation = [
        ['href' => '#', 'icon' => 'heroicon-o-book-open', 'label' => 'Guide utilisateur', 'meta' => 'Documentation complète du CRM'],
        ['href' => '#', 'icon' => 'heroicon-o-document-text', 'label' => 'Manuel technique', 'meta' => 'Guide d\'installation et configuration'],
    ];
@endphp

<div class="space-y-6">
    <!-- En-tête -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-semibold tracking-tight text-gray-800 dark:text-gray-100">
                Vue d'ensemble
            </h2>
            <p class="text-sm text-gray-400 dark:text-gray-500">
                Super Administration · {{ now()->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400 dark:ring-emerald-400/30">
                ● Système opérationnel
            </span>
            <a href="/super-admin/database-manager" class="inline-flex items-center rounded-full bg-gray-50 px-3 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/20 hover:bg-gray-100 dark:bg-gray-800/50 dark:text-gray-300 dark:ring-gray-400/30 dark:hover:bg-gray-800">
                <span class="mr-1"></span> Base: {{ $stats['db_size'] }}
            </a>
        </div>
    </div>

    <!-- KPIs - Horizontal sur grand écran -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        @foreach ($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" 
               class="group relative overflow-hidden rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-200/50 transition-all hover:shadow-md hover:ring-gray-300/50 dark:bg-gray-800/50 dark:ring-gray-700/50 dark:hover:ring-gray-600/50">
                <div class="flex flex-col p-4">
                    <span class="text-xs font-medium uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        {{ $kpi['label'] }}
                    </span>
                    <span class="mt-1 text-2xl font-semibold text-gray-800 dark:text-gray-100">
                        {{ is_numeric($kpi['value']) ? number_format((float) $kpi['value'], 0, ',', ' ') : $kpi['value'] }}
                    </span>
                    <span class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                        {{ $kpi['sub'] }}
                    </span>
                </div>
                <!-- Petite barre de couleur discrète -->
                <div class="absolute bottom-0 left-0 h-0.5 w-full bg-{{ $kpi['color'] }}-500/20"></div>
                <div class="absolute bottom-0 left-0 h-0.5 w-0 bg-{{ $kpi['color'] }}-500 transition-all duration-300 group-hover:w-full"></div>
            </a>
        @endforeach
    </div>

    <!-- Contenu principal - 2 colonnes horizontales -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Répartition des rôles -->
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200/50 dark:bg-gray-800/50 dark:ring-gray-700/50">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700/50">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Répartition des rôles
                </h3>
                <span class="text-xs text-gray-400 dark:text-gray-500">
                    {{ $roles->sum('users_count') }} utilisateurs
                </span>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @forelse ($roles as $role)
                        @php
                            $share = round(($role->users_count / $roleTotal) * 100);
                            $width = round(($role->users_count / $roleMax) * 100);
                        @endphp
                        <div>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                        {{ $role->name }}
                                    </span>
                                    <span class="ml-2 text-xs text-gray-400 dark:text-gray-500">
                                        {{ $share }}%
                                    </span>
                                </div>
                                <span class="text-sm text-gray-600 dark:text-gray-300">
                                    {{ $role->users_count }}
                                </span>
                            </div>
                            <div class="mt-1 h-1 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                <div class="h-full rounded-full bg-indigo-400 transition-all duration-500 dark:bg-indigo-500" 
                                     style="width: {{ $width }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 dark:text-gray-500">Aucun rôle disponible</p>
                    @endforelse
                </div>
            </div>
        </section>

        <!-- Derniers utilisateurs -->
        <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200/50 dark:bg-gray-800/50 dark:ring-gray-700/50">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-700/50">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    Derniers utilisateurs
                </h3>
                <a href="/super-admin/users" class="text-xs text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300">
                    Voir tout →
                </a>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700/50">
                @forelse ($recent as $user)
                    @php
                        $fullName = trim(($user->prenom ?? '').' '.($user->nom ?? '')) ?: ($user->name ?? 'Utilisateur');
                    @endphp
                    <a href="/super-admin/users/{{ $user->id }}/edit" 
                       class="flex items-center justify-between px-6 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <div>
                            <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                {{ $fullName }}
                            </div>
                            <div class="text-xs text-gray-400 dark:text-gray-500">
                                {{ $user->email }}
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{ $user->created_at?->diffForHumans() ?? 'n/a' }}
                        </span>
                    </a>
                @empty
                    <div class="px-6 py-4 text-sm text-gray-400 dark:text-gray-500">
                        Aucun utilisateur récent
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <!-- Accès rapides - Disposition horizontale -->
    <section class="rounded-xl bg-white shadow-sm ring-1 ring-gray-200/50 dark:bg-gray-800/50 dark:ring-gray-700/50">
        <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-700/50">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                Accès rapides
            </h3>
        </div>
        <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" 
                   class="group flex items-center gap-3 rounded-lg p-3 transition hover:bg-gray-50 dark:hover:bg-gray-700/30">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-500 dark:bg-indigo-500/10 dark:text-indigo-400">
                        <x-dynamic-component :component="$link['icon']" class="h-4 w-4" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ $link['label'] }}
                        </div>
                        <div class="truncate text-xs text-gray-400 dark:text-gray-500">
                            {{ $link['meta'] }}
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="espo-dashlet">
        <div class="espo-dashlet-header">
            <h3 class="espo-dashlet-title">Documentation</h3>
            <span class="espo-badge-soft">Guides et manuels</span>
        </div>
        <div class="espo-dashlet-body">
            <div class="espo-dashlet-grid espo-dashlet-grid-2">
                @foreach ($documentation as $doc)
                    <a class="espo-action-row" href="{{ $doc['href'] }}">
                        <span class="espo-action-icon">
                            <x-dynamic-component :component="$doc['icon']" />
                        </span>
                        <span class="espo-list-main">
                            <span class="espo-list-title">{{ $doc['label'] }}</span>
                            <span class="espo-list-sub">{{ $doc['meta'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</div>
</x-filament-panels::page>