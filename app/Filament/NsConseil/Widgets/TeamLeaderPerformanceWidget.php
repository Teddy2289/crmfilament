<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Concerns\HasDashboardDateRange;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmSettingsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class TeamLeaderPerformanceWidget extends BaseWidget
{
    use HasDashboardDateRange;
    use InteractsWithPageFilters;

    protected static ?string $heading = '📊 Statistiques & Performance des utilisateurs (Jour / Semaine / Mois / Filtre Date)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    public function table(Table $table): Table
    {
        $roles = app(CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur', 'commercial']);

        [$startDate, $endDate] = $this->getDashboardDateRange();

        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();
        $debutMois = now()->startOfMonth();
        $finMois = now()->endOfMonth();

        // Preload all stats to avoid N+1 queries
        $userIds = User::query()
            ->where(function ($q) use ($roles) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));
                foreach ($roles as $role) {
                    $q->orWhere('role_cache', $role);
                }
            })
            ->where('actif', true)
            ->pluck('id')
            ->toArray();

        // Preload appels stats for all periods
        $appelsStats = Appel::query()
            ->where('appelable_type', Prospect::class)
            ->whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id')
            ->map(fn ($appels) => [
                'jour' => $appels->where('date_heure', '>=', today()->startOfDay())->where('date_heure', '<=', today()->endOfDay())->count(),
                'semaine' => $appels->where('date_heure', '>=', $debutSemaine)->where('date_heure', '<=', $finSemaine)->count(),
                'mois' => $appels->where('date_heure', '>=', $debutMois)->where('date_heure', '<=', $finMois)->count(),
                'periode' => $appels->where('date_heure', '>=', $startDate)->where('date_heure', '<=', $endDate)->count(),
                'joints_semaine' => $appels->where('date_heure', '>=', $debutSemaine)->where('date_heure', '<=', $finSemaine)->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])->count(),
                'joints_mois' => $appels->where('date_heure', '>=', $debutMois)->where('date_heure', '<=', $finMois)->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])->count(),
                'joints_periode' => $appels->where('date_heure', '>=', $startDate)->where('date_heure', '<=', $endDate)->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])->count(),
                'joints_jour' => $appels->where('date_heure', '>=', today()->startOfDay())->where('date_heure', '<=', today()->endOfDay())->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])->count(),
            ]);

        // Preload prospects stats
        $prospectsStats = Prospect::query()
            ->whereIn('teleprospecteur_id', $userIds)
            ->get()
            ->groupBy('teleprospecteur_id')
            ->map(fn ($prospects) => [
                'qf_jour' => $prospects->where('statut', ProspectStatut::QF->value)->where('qf_valide_at', '>=', today()->startOfDay())->where('qf_valide_at', '<=', today()->endOfDay())->count(),
                'qf_semaine' => $prospects->where('statut', ProspectStatut::QF->value)->where('qf_valide_at', '>=', $debutSemaine)->where('qf_valide_at', '<=', $finSemaine)->count(),
                'qf_mois' => $prospects->where('statut', ProspectStatut::QF->value)->where('qf_valide_at', '>=', $debutMois)->where('qf_valide_at', '<=', $finMois)->count(),
                'qf_periode' => $prospects->where('statut', ProspectStatut::QF->value)->where('qf_valide_at', '>=', $startDate)->where('qf_valide_at', '<=', $endDate)->count(),
                'statuts' => [
                    'ac' => $prospects->where('statut', ProspectStatut::AC->value)->count(),
                    'std_nr' => $prospects->where('statut', ProspectStatut::STD_NR->value)->count(),
                    'std_joint' => $prospects->where('statut', ProspectStatut::STD_Joint->value)->count(),
                    'cse_nr' => $prospects->where('statut', ProspectStatut::CSE_NR->value)->count(),
                    'rp' => $prospects->where('statut', ProspectStatut::RP->value)->count(),
                    'rpc' => $prospects->where('statut', ProspectStatut::RPC->value)->where('updated_at', '<', now()->subDays(5))->count(),
                    'rpc_total' => $prospects->where('statut', ProspectStatut::RPC->value)->count(),
                    'ko' => $prospects->where('statut', ProspectStatut::KO->value)->count(),
                    'qf' => $prospects->where('statut', ProspectStatut::QF->value)->count(),
                ],
            ]);

        // Preload last call date for alerts
        $dernierAppelPar = Appel::query()
            ->where('appelable_type', Prospect::class)
            ->whereIn('user_id', $userIds)
            ->orderBy('date_heure', 'desc')
            ->get()
            ->groupBy('user_id')
            ->map(fn ($appels) => $appels->first()?->date_heure);

        return $table
            ->query(
                User::query()
                    ->where(function ($q) use ($roles) {
                        $q->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));
                        foreach ($roles as $role) {
                            $q->orWhere('role_cache', $role);
                        }
                    })
                    ->where('actif', true)
                    ->orderBy('nom')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nom_complet')
                    ->label('Utilisateur')
                    ->state(fn (User $record) => trim("{$record->prenom} {$record->nom}"))
                    ->description(fn (User $record) => $record->role_cache ?? 'Téléprospecteur')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('stats_jour')
                    ->label('Aujourd\'hui')
                    ->state(function (User $record) use ($appelsStats, $prospectsStats) {
                        $stats = $appelsStats->get($record->id, []);
                        $prospectStats = $prospectsStats->get($record->id, []);
                        $appels = $stats['jour'] ?? 0;
                        $joints = $stats['joints_jour'] ?? 0;
                        $qf = $prospectStats['qf_jour'] ?? 0;

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stats_semaine')
                    ->label('Cette Semaine')
                    ->state(function (User $record) use ($appelsStats, $prospectsStats) {
                        $stats = $appelsStats->get($record->id, []);
                        $prospectStats = $prospectsStats->get($record->id, []);
                        $appels = $stats['semaine'] ?? 0;
                        $joints = $stats['joints_semaine'] ?? 0;
                        $qf = $prospectStats['qf_semaine'] ?? 0;

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('stats_mois')
                    ->label('Ce Mois')
                    ->state(function (User $record) use ($appelsStats, $prospectsStats) {
                        $stats = $appelsStats->get($record->id, []);
                        $prospectStats = $prospectsStats->get($record->id, []);
                        $appels = $stats['mois'] ?? 0;
                        $joints = $stats['joints_mois'] ?? 0;
                        $qf = $prospectStats['qf_mois'] ?? 0;

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('stats_periode_filtre')
                    ->label('Période (Filtre Date)')
                    ->state(function (User $record) use ($appelsStats, $prospectsStats, $startDate, $endDate) {
                        $stats = $appelsStats->get($record->id, []);
                        $prospectStats = $prospectsStats->get($record->id, []);
                        $appels = $stats['periode'] ?? 0;
                        $joints = $stats['joints_periode'] ?? 0;
                        $qf = $prospectStats['qf_periode'] ?? 0;

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->description(fn () => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'))
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('taux_conversion')
                    ->label('Taux (Filtre)')
                    ->state(function (User $record) use ($appelsStats) {
                        $stats = $appelsStats->get($record->id, []);
                        $appels = $stats['periode'] ?? 0;

                        if ($appels === 0) {
                            return '—';
                        }

                        $joints = $stats['joints_periode'] ?? 0;
                        return round(($joints / $appels) * 100, 1).'%';
                    })
                    ->alignCenter()
                    ->weight('bold'),

                // ── Prospects par statut (valeurs actuelles) ──
                Tables\Columns\TextColumn::make('statut_ac')
                    ->label('AC')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['ac'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('statut_std_nr')
                    ->label('STD NR')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['std_nr'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('orange'),

                Tables\Columns\TextColumn::make('statut_std_joint')
                    ->label('STD Joint')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['std_joint'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('blue'),

                Tables\Columns\TextColumn::make('statut_cse_nr')
                    ->label('CSE NR')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['cse_nr'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('amber'),

                Tables\Columns\TextColumn::make('statut_rp')
                    ->label('RP')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['rp'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('indigo'),

                Tables\Columns\TextColumn::make('statut_rpc')
                    ->label('RPC')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['rpc_total'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('teal')
                    ->color(fn ($state) => $state > 10 ? 'danger' : 'teal'),

                Tables\Columns\TextColumn::make('statut_ko')
                    ->label('KO')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['ko'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('red'),

                Tables\Columns\TextColumn::make('statut_qf')
                    ->label('QF')
                    ->state(function (User $record) use ($prospectsStats) {
                        $stats = $prospectsStats->get($record->id, []);
                        return $stats['statuts']['qf'] ?? 0;
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('green'),

                Tables\Columns\TextColumn::make('alerte')
                    ->label('Alerte')
                    ->state(function (User $record) use ($dernierAppelPar, $prospectsStats) {
                        $alertes = [];

                        $dernierAppel = $dernierAppelPar->get($record->id);

                        if (! $dernierAppel || $dernierAppel->diffInDays(now()) >= 2) {
                            $alertes[] = 'Sans appel 2j+';
                        }

                        $stats = $prospectsStats->get($record->id, []);
                        $rpcAncien = $stats['statuts']['rpc'] ?? 0;

                        if ($rpcAncien > 0) {
                            $alertes[] = "{$rpcAncien} RPC > 5j";
                        }

                        return $alertes ? implode(' · ', $alertes) : '—';
                    })
                    ->color(fn ($state) => $state !== '—' ? 'danger' : 'gray'),
            ])
            ->emptyStateHeading('Aucun utilisateur actif');
    }
}
