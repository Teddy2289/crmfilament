<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
use App\Enums\ProspectStatut;
use App\Enums\RendezVousStatut;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommercialKpiWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('commercial')
                || $user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $isCommercial = $user->hasRoleCache('commercial');

        $debutSemaine = now()->startOfWeek();
        $finSemaine = now()->endOfWeek();
        $debutSemainePrecedente = now()->subWeek()->startOfWeek();
        $finSemainePrecedente = now()->subWeek()->endOfWeek();

        // Partenaires actifs
        $partenaireQuery = Partenaire::query()
            ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));

        $partenairesActifs = (clone $partenaireQuery)
            ->whereIn('statut', [
                OrganizationStatus::ConventionEngagement->value,
                OrganizationStatus::SigneAccordCadre->value,
            ])
            ->count();

        // RDV semaine passée
        $rdvQuery = RendezVous::query()
            ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));

        $rdvRealises = (clone $rdvQuery)
            ->where('statut', RendezVousStatut::Realise)
            ->whereBetween('date_heure', [$debutSemainePrecedente, $finSemainePrecedente])
            ->count();

        $rdvAnnules = (clone $rdvQuery)
            ->where('statut', RendezVousStatut::Annule)
            ->whereBetween('date_heure', [$debutSemainePrecedente, $finSemainePrecedente])
            ->count();

        $rdvDecales = (clone $rdvQuery)
            ->where('statut', RendezVousStatut::Decale)
            ->whereBetween('date_heure', [$debutSemainePrecedente, $finSemainePrecedente])
            ->count();

        $totalRdvSemPassee = $rdvRealises + $rdvAnnules + $rdvDecales;
        $tauxNoShow = $totalRdvSemPassee > 0
            ? round(($rdvAnnules / $totalRdvSemPassee) * 100, 1)
            : 0;

        // RDV à venir cette semaine
        $rdvAVenir = (clone $rdvQuery)
            ->whereIn('statut', [RendezVousStatut::Planifie->value, RendezVousStatut::Decale->value])
            ->whereBetween('date_heure', [now(), $finSemaine])
            ->count();

        // Pipeline prospects RP/RPC en attente
        $prospectQuery = Prospect::query()
            ->when($isCommercial, fn ($q) => $q->where('commercial_id', $user->id));

        $pipelineRpRpc = (clone $prospectQuery)
            ->whereIn('statut', [ProspectStatut::RP->value, ProspectStatut::RPC->value])
            ->count();

        return [
            Stat::make('Partenaires actifs', $partenairesActifs)
                ->icon('heroicon-o-building-office-2')
                ->color('success'),

            Stat::make('RDV sem. passée', $rdvRealises)
                ->description("{$rdvAnnules} annulés · {$rdvDecales} décalés")
                ->icon('heroicon-o-calendar-days')
                ->color('primary'),

            Stat::make('Taux no-show', "{$tauxNoShow}%")
                ->description("{$rdvAVenir} RDV cette semaine")
                ->icon('heroicon-o-exclamation-triangle')
                ->color($tauxNoShow > 20 ? 'danger' : 'success'),

            Stat::make('Pipeline RP/RPC', $pipelineRpRpc)
                ->description('Prospects en attente')
                ->icon('heroicon-o-funnel')
                ->color($pipelineRpRpc > 0 ? 'warning' : 'gray'),
        ];
    }
}
