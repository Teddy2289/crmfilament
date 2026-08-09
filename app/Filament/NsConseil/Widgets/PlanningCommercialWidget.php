<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use App\Models\Partenaire;
use App\Models\RendezVous;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlanningCommercialWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'sm' => 12,
        'md' => 12,
        'lg' => 6,
        'xl' => 6,
        '2xl' => 4,
    ];

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user
            && ($user->hasRoleCache('commercial')
                || $user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $user = Auth::user();
        $isCommercial = $user->hasRoleCache('commercial');

        $aujourdhui = now()->startOfDay();
        $demain = now()->addDay()->startOfDay();
        $finSemaine = now()->endOfWeek();

        // RDV du jour
        $rdvQuery = RendezVous::query()
            ->whereBetween('date_heure', [$aujourdhui, $demain])
            ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));

        $rdvAujourdhui = (clone $rdvQuery)->count();
        $rdvRealises = (clone $rdvQuery)->where('statut', RendezVousStatut::Realise)->count();
        $rdvPlanifies = (clone $rdvQuery)->whereIn('statut', [RendezVousStatut::Planifie, RendezVousStatut::Decale])->count();

        // Permanences de la semaine
        $permanencesQuery = RendezVous::query()
            ->where('type', RendezVousType::Permanence)
            ->whereBetween('date_heure', [$aujourdhui, $finSemaine])
            ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));

        $permanencesSemaine = (clone $permanencesQuery)->count();

        // RDV avec partenaires cette semaine
        $rdvPartenairesQuery = RendezVous::query()
            ->where('rdvable_type', Partenaire::class)
            ->whereBetween('date_heure', [$aujourdhui, $finSemaine])
            ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));

        $rdvPartenaires = (clone $rdvPartenairesQuery)->count();

        // Partenaires visités cette semaine
        $partenairesVisites = (clone $rdvPartenairesQuery)
            ->where('statut', RendezVousStatut::Realise)
            ->distinct('rdvable_id')
            ->count('rdvable_id');

        return [
            Stat::make('RDV aujourd\'hui', $rdvAujourdhui)
                ->description("{$rdvRealises} réalisés · {$rdvPlanifies} planifiés")
                ->icon('heroicon-o-calendar-days')
                ->color($rdvPlanifies > 0 ? 'primary' : 'gray'),

            Stat::make('Permanences semaine', $permanencesSemaine)
                ->description('Cette semaine')
                ->icon('heroicon-o-clock')
                ->color($permanencesSemaine > 0 ? 'info' : 'gray'),

            Stat::make('RDV partenaires', $rdvPartenaires)
                ->description("{$partenairesVisites} partenaires visités")
                ->icon('heroicon-o-building-office-2')
                ->color($rdvPartenaires > 0 ? 'success' : 'gray'),
        ];
    }
}
