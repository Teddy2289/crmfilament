<?php

namespace App\Livewire;

use App\Models\Prospect;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TeamLeaderUserStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '60s';
    protected static ?int $sort = 2;
    protected static bool $isLazy = false;

    protected function getViewData(): array
    {
        return [
            'data-widget' => 'team-leader-user-stats',
        ];
    }

    protected function getStats(): array
    {
        $userId = auth()->id();
        $user = User::find($userId);

        // Si l'utilisateur est un Team Leader, obtenir les stats de son équipe
        $teamUserIds = $this->getTeamUserIds($user);
        $teamUsers = User::whereIn('id', $teamUserIds)->get();

        // Données pour la semaine en cours
        $currentWeekStart = now()->startOfWeek();
        $currentWeekEnd = now()->endOfWeek();

        $stats = [];
        foreach ($teamUsers as $teamUser) {
            $conversionsQF = Prospect::where('teleprospecteur_id', $teamUser->id)
                ->where('statut', 'QF')
                ->whereBetween('updated_at', [$currentWeekStart, $currentWeekEnd])
                ->count();

            $conversionsPartenaire = Prospect::where('teleprospecteur_id', $teamUser->id)
                ->whereNotNull('converti_partenaire_id')
                ->whereBetween('updated_at', [$currentWeekStart, $currentWeekEnd])
                ->count();

            $totalConversions = $conversionsQF + $conversionsPartenaire;

            $stats[] = BaseWidget\Stat::make($teamUser->name, $totalConversions)
                ->description("QF: {$conversionsQF} | Partenaire: {$conversionsPartenaire}")
                ->descriptionIcon('heroicon-o-user')
                ->color($totalConversions > 0 ? 'success' : 'gray');
        }

        return $stats;
    }

    protected function getTeamUserIds(User $user): array
    {
        // Si l'utilisateur est un Team Leader ou Super Admin, obtenir tous les utilisateurs
        if ($user->hasRole(['team_leader', 'super_admin', 'administrateur'])) {
            return User::role(['teleprospecteur', 'commercial'])->pluck('id')->toArray();
        }

        // Sinon, retourner uniquement l'ID de l'utilisateur
        return [$user->id];
    }
}
