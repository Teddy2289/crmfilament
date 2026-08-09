<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\Prospect;
use App\Models\Partenaire;
use App\Models\Client;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ConversionFunnelWidget extends ChartWidget
{
    protected static ?string $heading = 'Entonnoir de Conversion';

    protected static ?int $sort = 4;

    protected static string $color = 'warning';

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
        return [
            'datasets' => [
                [
                    'label' => 'Conversion',
                    'data' => [
                        Prospect::count(),
                        Partenaire::count(),
                        Client::count(),
                    ],
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                    ],
                ],
            ],
            'labels' => ['Prospects', 'Partenaires', 'Clients'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
