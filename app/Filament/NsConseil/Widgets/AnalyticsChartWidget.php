<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\Prospect;
use App\Models\Partenaire;
use App\Models\Client;
use App\Models\Opportunite;
use App\Models\RendezVous;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AnalyticsChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Évolution des prospects';

    protected static ?int $sort = 2;

    protected static string $color = 'info';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getData(): array
    {
        $months = [];
        $prospects = [];
        $partenaires = [];
        $clients = [];
        $rdvs = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');
            
            $prospects[] = Prospect::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $partenaires[] = Partenaire::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $clients[] = Client::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();
            
            $rdvs[] = RendezVous::whereMonth('date_heure', $date->month)
                ->whereYear('date_heure', $date->year)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Prospects',
                    'data' => $prospects,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
                [
                    'label' => 'Partenaires',
                    'data' => $partenaires,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
                [
                    'label' => 'Clients',
                    'data' => $clients,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.5)',
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
                [
                    'label' => 'RDVs',
                    'data' => $rdvs,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgb(239, 68, 68)',
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
