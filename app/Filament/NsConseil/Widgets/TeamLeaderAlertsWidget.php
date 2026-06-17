<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Enums\RendezVousStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TeamLeaderAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        // Rappels en retard non traités
        $rappelsRetard = Prospect::where('rappel_planifie_at', '<', now())
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();

        // RPC > 5 jours sans suite
        $rpcAnciens = Prospect::where('statut', ProspectStatut::RPC->value)
            ->where('updated_at', '<', now()->subDays(5))
            ->count();

        // QF en attente de validation
        $qfEnAttente = Prospect::where('statut', ProspectStatut::QF->value)
            ->where('qf_valide', false)
            ->count();

        // Taux no-show global commerciaux (semaine passée)
        $totalRdv = RendezVous::whereBetween('date_heure', [
            now()->subWeek()->startOfWeek(),
            now()->subWeek()->endOfWeek(),
        ])->count();

        $rdvAnnules = RendezVous::where('statut', RendezVousStatut::Annule)
            ->whereBetween('date_heure', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek(),
            ])
            ->count();

        $tauxNoShow = $totalRdv > 0 ? round(($rdvAnnules / $totalRdv) * 100, 1) : 0;

        return [
            Stat::make('Rappels en retard', $rappelsRetard)
                ->description('Non traités')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($rappelsRetard > 0 ? 'danger' : 'success'),

            Stat::make('RPC > 5 jours', $rpcAnciens)
                ->description('Sans suite depuis 5j+')
                ->icon('heroicon-o-clock')
                ->color($rpcAnciens > 0 ? 'warning' : 'success'),

            Stat::make('QF à valider', $qfEnAttente)
                ->description('En attente validation TL')
                ->icon('heroicon-o-check-badge')
                ->color($qfEnAttente > 0 ? 'info' : 'gray'),

            Stat::make('No-show global', "{$tauxNoShow}%")
                ->description('Semaine passée')
                ->icon('heroicon-o-x-circle')
                ->color($tauxNoShow > 20 ? 'danger' : 'success'),
        ];
    }
}
