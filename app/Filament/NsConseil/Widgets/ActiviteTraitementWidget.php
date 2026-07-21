<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ActiviteTraitementWidget extends BaseWidget
{
    protected static ?int $sort = 0;

    protected static ?string $pollingInterval = '60s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('teleprospecteur')
                || $user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getStats(): array
    {
        $user = auth()->user();
        $isTp = $user->hasRoleCache('teleprospecteur');

        $query = Prospect::query()
            ->when($isTp, fn ($q) => $q->where('teleprospecteur_id', $user->id));

        $vierges = (clone $query)->vierges()->count();
        $enCours = (clone $query)->enCoursDeTraitement()->count();
        $finalisees = (clone $query)
            ->whereIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->count();

        $totalTravaillees = $enCours + $finalisees;
        $totalFiches = $vierges + $totalTravaillees;
        $tauxTraitement = $totalFiches > 0
            ? round(($totalTravaillees / $totalFiches) * 100, 1)
            : 0;

        return [
            Stat::make('Fiches vierges', $vierges)
                ->description('Jamais appelées')
                ->icon('heroicon-o-document')
                ->color('gray'),

            Stat::make('En cours de traitement', $enCours)
                ->description('Appelées, pas encore finalisées')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('Fiches difficiles', (clone $query)->difficiles()->count())
                ->description('3 tentatives infructueuses')
                ->icon('heroicon-o-flag')
                ->color('danger'),

            Stat::make('Taux de traitement', "{$tauxTraitement}%")
                ->description("{$finalisees} finalisées (KO/QF)")
                ->icon('heroicon-o-chart-pie')
                ->color($tauxTraitement >= 50 ? 'success' : 'gray'),
        ];
    }
}
