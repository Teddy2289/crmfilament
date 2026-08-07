<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
use App\Filament\NsConseil\Concerns\HasDashboardDateRange;
use App\Enums\ProspectStatut;
use App\Enums\RendezVousStatut;
use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DirectionKpiWidget extends BaseWidget
{
    use HasDashboardDateRange;

    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '120s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        [$startDate, $endDate] = $this->getDashboardDateRange();

        $clients3mois = Client::whereBetween('created_at', [$startDate->copy()->subMonths(3), $endDate])->count();

        $partenairesSignes = Partenaire::whereIn('statut', [
            OrganizationStatus::SigneAccordCadre->value,
            OrganizationStatus::ConventionEngagement->value,
        ])->whereBetween('created_at', [$startDate, $endDate])->count();

        $prospectsActifs = Prospect::whereNotIn('statut', [
            ProspectStatut::KO->value,
            ProspectStatut::QF->value,
        ])->whereBetween('created_at', [$startDate, $endDate])->count();

        $qfMois = Prospect::where('statut', ProspectStatut::QF->value)
            ->whereBetween('qf_valide_at', [$startDate, $endDate])
            ->count();

        $rdvMois = RendezVous::whereBetween('date_heure', [$startDate, $endDate])
            ->count();

        $rdvRealises = RendezVous::where('statut', RendezVousStatut::Realise)
            ->whereBetween('date_heure', [$startDate, $endDate])
            ->count();

        $tauxTransformation = $rdvMois > 0 ? round(($rdvRealises / $rdvMois) * 100, 1) : 0;

        return [
            Stat::make('Clients (3 derniers mois)', $clients3mois)
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Partenaires signés', $partenairesSignes)
                ->icon('heroicon-o-building-office-2')
                ->color('success'),

            Stat::make('Prospects actifs', $prospectsActifs)
                ->description("{$qfMois} QF ce mois")
                ->icon('heroicon-o-funnel')
                ->color('info'),

            Stat::make('Taux transformation RDV', "{$tauxTransformation}%")
                ->description("{$rdvRealises}/{$rdvMois} ce mois")
                ->icon('heroicon-o-chart-bar')
                ->color($tauxTransformation >= 50 ? 'success' : 'warning'),
        ];
    }
}
