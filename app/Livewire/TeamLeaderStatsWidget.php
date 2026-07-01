<?php

namespace App\Livewire;

use App\Models\Prospect;
use App\Models\Partenaire;
use App\Models\Appel;
use App\Models\RendezVous;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TeamLeaderStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';

    protected function getViewData(): array
    {
        return [
            'data-widget' => 'team-leader-stats',
        ];
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $user = User::find($userId);

        // Si l'utilisateur est responsable d'équipe, obtenir les stats de son équipe
        $teamUserIds = $this->getTeamUserIds($user);

        return [
            BaseWidget\Stat::make('Prospects Actifs', Prospect::whereIn('teleprospecteur_id', $teamUserIds)
                ->whereNotIn('statut', ['KO', 'QF'])
                ->count())
                ->description('Équipe')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            BaseWidget\Stat::make('Conversions QF', Prospect::whereIn('teleprospecteur_id', $teamUserIds)
                ->where('statut', 'QF')
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count())
                ->description('Cette semaine')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            BaseWidget\Stat::make('Conversions Partenaire', Prospect::whereIn('teleprospecteur_id', $teamUserIds)
                ->whereNotNull('converti_partenaire_id')
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count())
                ->description('Cette semaine')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('primary'),

            BaseWidget\Stat::make('Appels Réalisés', Appel::whereIn('user_id', $teamUserIds)
                ->whereBetween('date_heure', [now()->startOfWeek(), now()->endOfWeek()])
                ->count())
                ->description('Cette semaine')
                ->descriptionIcon('heroicon-o-phone')
                ->color('warning'),

            BaseWidget\Stat::make('RDV Planifiés', RendezVous::whereIn('commercial_id', $teamUserIds)
                ->where('statut', 'Planifie')
                ->whereBetween('date_heure', [now()->startOfWeek(), now()->endOfWeek()])
                ->count())
                ->description('Cette semaine')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('purple'),

            BaseWidget\Stat::make('Partenaires Actifs', Partenaire::whereIn('conseiller_id', $teamUserIds)
                ->where('statut', 'convention_engagement')
                ->count())
                ->description('Total')
                ->descriptionIcon('heroicon-o-star')
                ->color('success'),
        ];
    }

    protected function getTeamUserIds(User $user): array
    {
        // Si l'utilisateur est responsable d'équipe ou super administrateur, obtenir tous les utilisateurs
        if ($user->hasRole(['team_leader', 'super_admin', 'administrateur'])) {
            return User::role(['teleprospecteur', 'commercial'])->pluck('id')->toArray();
        }

        // Sinon, retourner uniquement l'ID de l'utilisateur
        return [$user->id];
    }
}
