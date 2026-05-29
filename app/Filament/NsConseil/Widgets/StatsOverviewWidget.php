<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
use App\Enums\ProspectStatut;
use App\Models\Partenaire;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();

        // Filtre selon rôle — commercial voit ses propres données
        $partenaireQuery = Partenaire::query();
        $prospectQuery = Prospect::query();

        if ($user->hasRole('commercial')) {
            $partenaireQuery->where('commercial_id', $user->id);
            $prospectQuery->where(fn ($q) => $q
                ->where('teleprospecteur_id', $user->id)
                ->orWhere('commercial_id', $user->id)
            );
        } elseif ($user->hasRole('teleprospecteur')) {
            $prospectQuery->where('teleprospecteur_id', $user->id);
        }

        // Statistiques Partenaires
        $conventions = (clone $partenaireQuery)
            ->where('statut', OrganizationStatus::ConventionEngagement->value)
            ->count();

        $signes = (clone $partenaireQuery)
            ->where('statut', OrganizationStatus::SigneAccordCadre->value)
            ->count();

        $rdvEnCours = (clone $partenaireQuery)
            ->where('statut', OrganizationStatus::RdvEnCours->value)
            ->count();

        $rdv90j = (clone $partenaireQuery)
            ->where('statut', OrganizationStatus::RdvEnCours->value)
            ->where('date_modification_statut', '<', now()->subDays(90))
            ->count();

        // Statistiques Prospects
        $prospectsActifs = (clone $prospectQuery)
            ->whereNotIn('statut', [
                ProspectStatut::KO->value,
                ProspectStatut::QF->value,
            ])
            ->count();

        $rappelsAujourdhui = (clone $prospectQuery)
            ->whereDate('rappel_planifie_at', today())
            ->count();

        $rappelsEnRetard = (clone $prospectQuery)
            ->where('rappel_planifie_at', '<', now())
            ->whereNotIn('statut', [
                ProspectStatut::KO->value,
                ProspectStatut::QF->value,
            ])
            ->count();

        $qfCeMois = (clone $prospectQuery)
            ->where('statut', ProspectStatut::QF->value)
            ->whereMonth('qf_valide_at', now()->month)
            ->count();

        return [
            Stat::make('Conventions actives', $conventions)
                ->description("+{$signes} signés accord cadre")
                ->icon('heroicon-o-building-office-2')
                ->color('success'),

            Stat::make('RDV en cours', $rdvEnCours)
                ->description($rdv90j > 0 ? "⚠️ {$rdv90j} dépassent 90 jours" : 'Tous dans les délais')
                ->icon('heroicon-o-calendar-days')
                ->color($rdv90j > 0 ? 'danger' : 'warning'),

            Stat::make('Prospects actifs', $prospectsActifs)
                ->description("{$rappelsAujourdhui} rappels aujourd'hui")
                ->icon('heroicon-o-funnel')
                ->color('primary'),

            Stat::make('QF ce mois', $qfCeMois)
                ->description($rappelsEnRetard > 0 ? "⚠️ {$rappelsEnRetard} rappels en retard" : 'Aucun retard')
                ->icon('heroicon-o-star')
                ->color($rappelsEnRetard > 0 ? 'danger' : 'success'),
        ];
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }
}
