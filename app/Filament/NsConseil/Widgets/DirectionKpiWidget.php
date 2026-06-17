<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
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
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $clients3mois = Client::where('created_at', '>=', now()->subMonths(3))->count();

        $partenairesSignes = Partenaire::whereIn('statut', [
            OrganizationStatus::SigneAccordCadre->value,
            OrganizationStatus::ConventionEngagement->value,
        ])->count();

        $prospectsActifs = Prospect::whereNotIn('statut', [
            ProspectStatut::KO->value,
            ProspectStatut::QF->value,
        ])->count();

        $qfMois = Prospect::where('statut', ProspectStatut::QF->value)
            ->whereMonth('qf_valide_at', now()->month)
            ->whereYear('qf_valide_at', now()->year)
            ->count();

        $rdvMois = RendezVous::whereMonth('date_heure', now()->month)
            ->whereYear('date_heure', now()->year)
            ->count();

        $rdvRealises = RendezVous::where('statut', RendezVousStatut::Realise)
            ->whereMonth('date_heure', now()->month)
            ->whereYear('date_heure', now()->year)
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
