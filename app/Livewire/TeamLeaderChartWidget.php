<?php

namespace App\Livewire;

use App\Models\Prospect;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TeamLeaderChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution des conversions';
    protected static ?string $pollingInterval = '60s';
    protected static string $color = 'info';

    protected function getData(): array
    {
        $userId = auth()->id();
        $user = User::find($userId);

        // Si l'utilisateur est un Team Leader, obtenir les stats de son équipe
        $teamUserIds = $this->getTeamUserIds($user);
        $teamUsers = User::whereIn('id', $teamUserIds)->get();

        // Données des 4 dernières semaines
        $weeks = [];
        $conversionsQF = [];
        $conversionsPartenaire = [];

        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            
            $weeks[] = $weekStart->format('d/m') . ' - ' . $weekEnd->format('d/m');
            
            $conversionsQF[] = Prospect::whereIn('teleprospecteur_id', $teamUserIds)
                ->where('statut', 'QF')
                ->whereBetween('updated_at', [$weekStart, $weekEnd])
                ->count();
            
            $conversionsPartenaire[] = Prospect::whereIn('teleprospecteur_id', $teamUserIds)
                ->whereNotNull('converti_partenaire_id')
                ->whereBetween('updated_at', [$weekStart, $weekEnd])
                ->count();
        }

        // Données détaillées par utilisateur pour la semaine en cours
        $currentWeekStart = now()->startOfWeek();
        $currentWeekEnd = now()->endOfWeek();
        
        $userStats = [];
        foreach ($teamUsers as $teamUser) {
            $userStats[] = [
                'name' => $teamUser->name,
                'conversions_qf' => Prospect::where('teleprospecteur_id', $teamUser->id)
                    ->where('statut', 'QF')
                    ->whereBetween('updated_at', [$currentWeekStart, $currentWeekEnd])
                    ->count(),
                'conversions_partenaire' => Prospect::where('teleprospecteur_id', $teamUser->id)
                    ->whereNotNull('converti_partenaire_id')
                    ->whereBetween('updated_at', [$currentWeekStart, $currentWeekEnd])
                    ->count(),
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Conversions QF',
                    'data' => $conversionsQF,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
                [
                    'label' => 'Conversions Partenaire',
                    'data' => $conversionsPartenaire,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $weeks,
            'user_stats' => $userStats,
        ];
    }

    protected function getType(): string
    {
        return 'line';
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
