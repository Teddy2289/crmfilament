<?php

namespace App\Filament\NsConseil\Resources\NsConseilResource\Widgets;

use App\Services\ReportGeneratorService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdvancedAnalytics extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        $reportService = app(ReportGeneratorService::class);
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $stats = $reportService->getDetailedPeriodStats($startDate, $endDate);

        return [
            Stat::make('Prospects du mois', $stats['prospects']['total'])
                ->description("Taux de conversion : " . number_format($stats['prospects']['conversion_rate'], 1) . '%')
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('info'),

            Stat::make('Clients créés', $stats['clients']['total'])
                ->description("Actifs : {$stats['clients']['active']}")
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('Opportunités', $stats['opportunites']['total'])
                ->description("Taux de réussite : " . number_format($stats['opportunites']['win_rate'], 1) . '%')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),

            Stat::make('Tâches complétées', $stats['tasks']['completed'])
                ->description("En retard : {$stats['tasks']['overdue']}")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color($stats['tasks']['overdue'] > 0 ? 'danger' : 'success'),
        ];
    }
}
