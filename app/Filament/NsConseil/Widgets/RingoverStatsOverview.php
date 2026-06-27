<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RingoverStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    protected static bool $isLazy = true;

    protected function getStats(): array
    {
        $stats = app(RingoverService::class)->getStats();

        $dureeMin = floor($stats['duree_moyenne'] / 60);
        $dureeSec = $stats['duree_moyenne'] % 60;
        $dureeLabel = "{$dureeMin}min {$dureeSec}s";

        return [
            Stat::make('Total appels', $stats['total'])
                ->description("{$stats['entrants']} entrants - {$stats['sortants']} sortants")
                ->color('primary')
                ->icon('heroicon-o-phone'),

            Stat::make('Taux de reponse', $stats['taux_reponse'].'%')
                ->description("{$stats['repondus']} repondus - {$stats['manques']} manques")
                ->color($stats['taux_reponse'] >= 80 ? 'success' : 'warning')
                ->icon('heroicon-o-check-circle'),

            Stat::make('Duree moyenne', $dureeLabel)
                ->description('Par appel')
                ->color('info')
                ->icon('heroicon-o-clock'),

            Stat::make('Entrants manques', $stats['manques_entrants'])
                ->description('Appels entrants sans reponse')
                ->color($stats['manques_entrants'] > 0 ? 'danger' : 'success')
                ->icon('heroicon-o-phone-x-mark'),
        ];
    }
}
