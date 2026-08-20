<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmSettingsService;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;

class TeamLeaderPerformanceWidget extends BaseWidget
{
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

        // Filament transmet normalement les filtres en camelCase. Le repli
        // snake_case + query string rend le filtre robuste aux liens directs
        // du type ?filters[startDate]=YYYY-MM-DD&filters[endDate]=YYYY-MM-DD.
        $filterStart = data_get($this->filters, 'startDate')
            ?? data_get($this->filters, 'start_date')
            ?? request()->input('filters.startDate')
            ?? request()->input('filters.start_date');
        $filterEnd = data_get($this->filters, 'endDate')
            ?? data_get($this->filters, 'end_date')
            ?? request()->input('filters.endDate')
            ?? request()->input('filters.end_date');

        $startDate = filled($filterStart)
            ? Carbon::parse($filterStart)->startOfDay()
            : now()->startOfMonth();

        $endDate = filled($filterEnd)
            ? Carbon::parse($filterEnd)->endOfDay()
            : now()->endOfMonth();

        $selectedUserId = data_get($this->filters, 'userId')
            ?? data_get($this->filters, 'user_id')
            ?? request()->input('filters.userId')
            ?? request()->input('filters.user_id');

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
                    ->when(filled($selectedUserId), fn ($query) => $query->whereKey($selectedUserId))
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
                    ->state(function (User $record) {
                        $appels = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereDate('date_heure', today())
                            ->count();

                        $joints = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereDate('date_heure', today())
                            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                            ->count();

                        $qf = Prospect::where('teleprospecteur_id', $record->id)
                            ->where('statut', ProspectStatut::QF->value)
                            ->whereDate('qf_valide_at', today())
                            ->count();

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('stats_semaine')
                    ->label('Cette Semaine')
                    ->state(function (User $record) {
                        $debut = now()->startOfWeek();
                        $fin = now()->endOfWeek();

                        $appels = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$debut, $fin])
                            ->count();

                        $joints = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$debut, $fin])
                            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                            ->count();

                        $qf = Prospect::where('teleprospecteur_id', $record->id)
                            ->where('statut', ProspectStatut::QF->value)
                            ->whereBetween('qf_valide_at', [$debut, $fin])
                            ->count();

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('stats_mois')
                    ->label('Ce Mois')
                    ->state(function (User $record) {
                        $debut = now()->startOfMonth();
                        $fin = now()->endOfMonth();

                        $appels = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$debut, $fin])
                            ->count();

                        $joints = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$debut, $fin])
                            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                            ->count();

                        $qf = Prospect::where('teleprospecteur_id', $record->id)
                            ->where('statut', ProspectStatut::QF->value)
                            ->whereBetween('qf_valide_at', [$debut, $fin])
                            ->count();

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('stats_periode_filtre')
                    ->label('Période (Filtre Date)')
                    ->state(function (User $record) use ($startDate, $endDate) {
                        $appels = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$startDate, $endDate])
                            ->count();

                        $joints = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$startDate, $endDate])
                            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                            ->count();

                        $qf = Prospect::where('teleprospecteur_id', $record->id)
                            ->where('statut', ProspectStatut::QF->value)
                            ->whereBetween('qf_valide_at', [$startDate, $endDate])
                            ->count();

                        return "{$appels} app. · {$joints} CSE · {$qf} QF";
                    })
                    ->description(fn () => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'))
                    ->alignCenter()
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('appels_par_statut')
                    ->label('Appels par statut')
                    ->state(function (User $record) use ($startDate, $endDate): string {
                        $labels = [
                            'ac' => 'AC',
                            'std_nr' => 'STD NR',
                            'std_joint' => 'STD Joint',
                            'cse_nr' => 'CSE NR',
                            'cse_ni' => 'CSE NI',
                            'rdv' => 'RDV',
                            'rapl_elu' => 'Rappel élu',
                            'rp' => 'RP',
                            'rpc' => 'RPC',
                            'ko' => 'KO',
                            'qf' => 'QF',
                        ];

                        $counts = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$startDate, $endDate])
                            ->selectRaw('phoning_status, COUNT(*) as total')
                            ->groupBy('phoning_status')
                            ->pluck('total', 'phoning_status');

                        foreach ($counts as $status => $count) {
                            $status = (string) $status;
                            if (! array_key_exists($status, $labels)) {
                                $labels[$status] = $status === ''
                                    ? 'Non renseigné'
                                    : ucwords(str_replace('_', ' ', $status));
                            }
                        }

                        return collect($labels)
                            ->map(fn (string $label, string $status): string => $label . ': ' . (int) ($counts[$status] ?? 0))
                            ->implode(' · ');
                    })
                    ->description(fn () => 'Appels du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y'))
                    ->wrap()
                    ->alignCenter()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('qualifications_periode')
                    ->label('Qualifications')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::QF->value)
                        ->whereBetween('qf_valide_at', [$startDate, $endDate])
                        ->count())
                    ->description(fn () => 'QF validées du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y'))
                    ->alignCenter()
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('taux_conversion')
                    ->label('Taux (Filtre)')
                    ->state(function (User $record) use ($startDate, $endDate) {
                        $appels = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$startDate, $endDate])
                            ->count();

                        if ($appels === 0) {
                            return '—';
                        }

                        $joints = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$startDate, $endDate])
                            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                            ->count();

                        return round(($joints / $appels) * 100, 1).'%';
                    })
                    ->alignCenter()
                    ->weight('bold'),

                // ── Prospects par statut (valeurs actuelles) ──
                Tables\Columns\TextColumn::make('statut_ac')
                    ->label('AC')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::AC->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('statut_std_nr')
                    ->label('STD NR')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::STD_NR->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('orange'),

                Tables\Columns\TextColumn::make('statut_std_joint')
                    ->label('STD Joint')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::STD_Joint->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('blue'),

                Tables\Columns\TextColumn::make('statut_cse_nr')
                    ->label('CSE NR')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::CSE_NR->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('amber'),

                Tables\Columns\TextColumn::make('statut_rp')
                    ->label('RP')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::RP->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('indigo'),

                Tables\Columns\TextColumn::make('statut_rpc')
                    ->label('RPC')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::RPC->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('teal')
                    ->color(fn ($state) => $state > 10 ? 'danger' : 'teal'),

                Tables\Columns\TextColumn::make('statut_ko')
                    ->label('KO')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::KO->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('red'),

                Tables\Columns\TextColumn::make('statut_qf')
                    ->label('QF')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::QF->value)
                        ->count())
                    ->alignCenter()
                    ->badge()
                    ->color('green'),

                Tables\Columns\TextColumn::make('alerte')
                    ->label('Alerte')
                    ->state(function (User $record) {
                        $alertes = [];

                        $dernierAppel = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->latest('date_heure')
                            ->first();

                        if (! $dernierAppel || $dernierAppel->date_heure->diffInDays(now()) >= 2) {
                            $alertes[] = 'Sans appel 2j+';
                        }

                        $rpcAncien = Prospect::where('teleprospecteur_id', $record->id)
                            ->where('statut', ProspectStatut::RPC->value)
                            ->where('updated_at', '<', now()->subDays(5))
                            ->count();

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
