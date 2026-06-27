<x-filament-panels::page>
@php
    $stats = $this->getStats();
    $roles = $this->getRolesDistribution();
    $recent = $this->getRecentUsers();
    $roleTotal = $roles->sum('users_count') ?: 1;
    $roleMax = $roles->max('users_count') ?: 1;

    $kpis = [
        ['label' => 'Utilisateurs', 'value' => $stats['users'], 'sub' => $stats['actifs'].' actifs', 'href' => '/super-admin/users'],
        ['label' => 'Roles', 'value' => $stats['roles'], 'sub' => $stats['permissions'].' permissions', 'href' => '/super-admin/roles'],
        ['label' => 'Tables BDD', 'value' => $stats['tables'], 'sub' => $stats['db_size'], 'href' => '/super-admin/database-manager'],
        ['label' => 'Imports', 'value' => $stats['imports'], 'sub' => "logs d'import", 'href' => '/super-admin/import-logs'],
        ['label' => 'Permissions', 'value' => $stats['permissions'], 'sub' => 'droits disponibles', 'href' => '/super-admin/roles'],
    ];

    $links = [
        ['href' => '/super-admin/users', 'icon' => 'heroicon-o-users', 'label' => 'Utilisateurs', 'meta' => 'Comptes, roles et statuts'],
        ['href' => '/super-admin/roles', 'icon' => 'heroicon-o-shield-check', 'label' => 'Roles', 'meta' => 'Permissions applicatives'],
        ['href' => '/super-admin/crm-profiles', 'icon' => 'heroicon-o-adjustments-horizontal', 'label' => 'Profils CRM', 'meta' => 'Capacites par profil'],
        ['href' => '/super-admin/crm-settings', 'icon' => 'heroicon-o-cog-6-tooth', 'label' => 'Parametres CRM', 'meta' => 'Dictionnaires et reglages'],
        ['href' => '/super-admin/pipeline-statuts', 'icon' => 'heroicon-o-bars-3-bottom-left', 'label' => 'Statuts pipeline', 'meta' => 'Etapes commerciales'],
        ['href' => '/super-admin/workflow-groupes', 'icon' => 'heroicon-o-arrow-path-rounded-square', 'label' => 'Workflows', 'meta' => 'Groupes et automatisations'],
        ['href' => '/super-admin/database-manager', 'icon' => 'heroicon-o-circle-stack', 'label' => 'Base de donnees', 'meta' => 'Inspection technique'],
        ['href' => '/super-admin/import-logs', 'icon' => 'heroicon-o-arrow-up-tray', 'label' => 'Logs imports', 'meta' => 'Historique des traitements'],
    ];
@endphp

<div class="espo-dashboard">
    <div class="espo-dashboard-header">
        <div>
            <h2 class="espo-dashboard-title">Vue d'ensemble</h2>
            <p class="espo-dashboard-meta">Super Administration - {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div class="espo-dashboard-actions">
            <span class="espo-badge-soft espo-badge-soft-success">Systeme operationnel</span>
            <a class="espo-badge-soft" href="/super-admin/database-manager">Base: {{ $stats['db_size'] }}</a>
        </div>
    </div>

    <div class="espo-kpi-grid">
        @foreach ($kpis as $kpi)
            <a class="espo-kpi" href="{{ $kpi['href'] }}">
                <span class="espo-kpi-label">{{ $kpi['label'] }}</span>
                <span class="espo-kpi-value">
                    {{ is_numeric($kpi['value']) ? number_format((float) $kpi['value'], 0, ',', ' ') : $kpi['value'] }}
                </span>
                <span class="espo-kpi-sub">{{ $kpi['sub'] }}</span>
            </a>
        @endforeach
    </div>

    <div class="espo-dashlet-grid">
        <section class="espo-dashlet">
            <div class="espo-dashlet-header">
                <h3 class="espo-dashlet-title">Repartition des roles</h3>
                <span class="espo-badge-soft">{{ $roles->sum('users_count') }} utilisateurs</span>
            </div>
            <div class="espo-dashlet-body">
                <div class="espo-list">
                    @forelse ($roles as $role)
                        @php
                            $share = round(($role->users_count / $roleTotal) * 100);
                            $width = round(($role->users_count / $roleMax) * 100);
                        @endphp
                        <div class="espo-list-row">
                            <div class="espo-list-main">
                                <div class="espo-list-title">{{ $role->name }}</div>
                                <div class="espo-list-sub">{{ $share }}% des comptes rattaches</div>
                            </div>
                            <div class="espo-list-value">{{ $role->users_count }}</div>
                        </div>
                        <div class="espo-progress" aria-hidden="true">
                            <div class="espo-progress-bar" style="--espo-progress-value: {{ $width }}%;"></div>
                        </div>
                    @empty
                        <div class="espo-list-row">
                            <div class="espo-list-main">
                                <div class="espo-list-title">Aucun role</div>
                                <div class="espo-list-sub">Aucune distribution disponible</div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="espo-dashlet">
            <div class="espo-dashlet-header">
                <h3 class="espo-dashlet-title">Derniers utilisateurs</h3>
                <a class="espo-badge-soft" href="/super-admin/users">Voir tout</a>
            </div>
            <div class="espo-dashlet-body">
                <div class="espo-list">
                    @forelse ($recent as $user)
                        @php
                            $fullName = trim(($user->prenom ?? '').' '.($user->nom ?? '')) ?: ($user->name ?? 'Utilisateur');
                        @endphp
                        <a class="espo-list-row" href="/super-admin/users/{{ $user->id }}/edit">
                            <div class="espo-list-main">
                                <div class="espo-list-title">{{ $fullName }}</div>
                                <div class="espo-list-sub">{{ $user->email }}</div>
                            </div>
                            <span class="espo-list-value">{{ $user->created_at?->diffForHumans() ?? 'n/a' }}</span>
                        </a>
                    @empty
                        <div class="espo-list-row">
                            <div class="espo-list-main">
                                <div class="espo-list-title">Aucun utilisateur</div>
                                <div class="espo-list-sub">Aucun compte recent</div>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>

    <section class="espo-dashlet">
        <div class="espo-dashlet-header">
            <h3 class="espo-dashlet-title">Acces rapides</h3>
            <span class="espo-badge-soft">Administration</span>
        </div>
        <div class="espo-dashlet-body">
            <div class="espo-dashlet-grid espo-dashlet-grid-3">
                @foreach ($links as $link)
                    <a class="espo-action-row" href="{{ $link['href'] }}">
                        <span class="espo-action-icon">
                            <x-dynamic-component :component="$link['icon']" />
                        </span>
                        <span class="espo-list-main">
                            <span class="espo-list-title">{{ $link['label'] }}</span>
                            <span class="espo-list-sub">{{ $link['meta'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</div>
</x-filament-panels::page>
