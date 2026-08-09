<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\Opportunite;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class OpportunitesStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Statistiques Opportunités';

    protected static ?int $sort = 3;

    protected static string $color = 'success';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $pollingInterval = '300s';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getData(): array
    {
        $stats = [
            'Nouvelles' => Opportunite::where('statut', 'nouvelle')->count(),
            'En cours' => Opportunite::where('statut', 'en_cours')->count(),
            'Gagnées' => Opportunite::where('statut', 'gagnee')->count(),
            'Perdues' => Opportunite::where('statut', 'perdue')->count(),
        ];

        return [
            'datasets' => [
                [
                    'data' => array_values($stats),
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(245, 158, 11)',
                        'rgb(16, 185, 129)',
                        'rgb(239, 68, 68)',
                    ],
                ],
            ],
            'labels' => array_keys($stats),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
