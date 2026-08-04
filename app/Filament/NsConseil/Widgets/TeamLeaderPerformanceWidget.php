<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmSettingsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TeamLeaderPerformanceWidget extends BaseWidget
{
    protected static ?string $heading = '📊 Performance téléprospecteurs — Semaine en cours';

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
        $roles = app(CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur']);

        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();

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
                    ->label('Téléprospecteur')
                    ->state(fn (User $record) => trim("{$record->prenom} {$record->nom}"))
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('appels_semaine')
                    ->label('Appels')
                    ->state(fn (User $record) => Appel::where('user_id', $record->id)
                        ->where('appelable_type', Prospect::class)
                        ->whereBetween('date_heure', [now()->startOfWeek(), now()->endOfWeek()])
                        ->count())
                    ->alignCenter()
                    ->color(fn ($state) => $state === 0 ? 'danger' : null),

                Tables\Columns\TextColumn::make('cse_joints')
                    ->label('CSE joints')
                    ->state(fn (User $record) => Appel::where('user_id', $record->id)
                        ->where('appelable_type', Prospect::class)
                        ->whereBetween('date_heure', [now()->startOfWeek(), now()->endOfWeek()])
                        ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                        ->count())
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('qf_semaine')
                    ->label('QF')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::QF->value)
                        ->whereBetween('qf_valide_at', [now()->startOfWeek(), now()->endOfWeek()])
                        ->count())
                    ->alignCenter()
                    ->color('success'),

                Tables\Columns\TextColumn::make('taux_conversion')
                    ->label('Taux')
                    ->state(function (User $record) use ($debutSemaine, $finSemaine) {
                        $appels = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$debutSemaine, $finSemaine])
                            ->count();

                        if ($appels === 0) {
                            return '—';
                        }

                        $joints = Appel::where('user_id', $record->id)
                            ->where('appelable_type', Prospect::class)
                            ->whereBetween('date_heure', [$debutSemaine, $finSemaine])
                            ->whereIn('phoning_status', ['std_joint', 'cse_ni', 'rdv', 'rapl_elu', 'rp', 'rpc'])
                            ->count();

                        return round(($joints / $appels) * 100, 1).'%';
                    })
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('base_restante')
                    ->label('Base AC')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->where('statut', ProspectStatut::AC->value)
                        ->count())
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('rp_en_attente')
                    ->label('RP/RPC')
                    ->state(fn (User $record) => Prospect::where('teleprospecteur_id', $record->id)
                        ->whereIn('statut', [ProspectStatut::RP->value, ProspectStatut::RPC->value])
                        ->count())
                    ->alignCenter()
                    ->color(fn ($state) => $state > 10 ? 'warning' : null),

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
            ->emptyStateHeading('Aucun téléprospecteur actif');
    }
}
