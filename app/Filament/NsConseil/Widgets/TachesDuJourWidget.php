<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\Task;
use Filament\Widgets\Widget;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class TachesDuJourWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getStats(): array
    {
        $userId = Auth::id();
        $aujourdhui = now()->startOfDay();
        $demain = now()->addDay()->startOfDay();

        $aFaire = Task::query()
            ->where('statut', 'a_faire')
            ->where('assigne_a', $userId)
            ->count();

        $enCours = Task::query()
            ->where('statut', 'en_cours')
            ->where('assigne_a', $userId)
            ->count();

        $enRetard = Task::query()
            ->where('statut', '!=', 'terminee')
            ->where('assigne_a', $userId)
            ->where('date_echeance', '<', now())
            ->count();

        $urgentes = Task::query()
            ->where('statut', '!=', 'terminee')
            ->where('assigne_a', $userId)
            ->whereBetween('date_echeance', [$aujourdhui, $demain])
            ->count();

        $termineesAujourdhui = Task::query()
            ->where('statut', 'terminee')
            ->where('assigne_a', $userId)
            ->whereDate('date_realisation', today())
            ->count();

        $total = Task::query()
            ->where('assigne_a', $userId)
            ->where('statut', '!=', 'terminee')
            ->count();

        return [
            Stat::make('À faire', $aFaire)
                ->description('Tâches en attente')
                ->descriptionIcon('heroicon-o-clock')
                ->color('gray'),

            Stat::make('En cours', $enCours)
                ->description('En progression')
                ->descriptionIcon('heroicon-o-play')
                ->color('warning'),

            Stat::make('En retard', $enRetard)
                ->description('Échéance dépassée')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($enRetard > 0 ? 'danger' : 'gray'),

            Stat::make('Urgentes', $urgentes)
                ->description('Pour aujourd\'hui')
                ->descriptionIcon('heroicon-o-fire')
                ->color($urgentes > 0 ? 'danger' : 'gray'),

            Stat::make('Terminées aujourd\'hui', $termineesAujourdhui)
                ->description('Accomplissement')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total en cours', $total)
                ->description('Toutes les tâches')
                ->descriptionIcon('heroicon-o-list-bullet')
                ->color('info'),
        ];
    }
}
