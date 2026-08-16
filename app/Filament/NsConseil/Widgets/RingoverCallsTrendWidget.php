<?php

namespace App\Filament\NsConseil\Widgets;

use App\Services\RingoverService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class RingoverCallsTrendWidget extends ChartWidget
{
    protected static ?string $heading = '📈 Tendance des Appels (7 derniers jours)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    protected static bool $isLazy = true;

    public ?string $errorMessage = null;

    protected function getData(): array
    {
        try {
            $ringoverService = app(RingoverService::class);
            
            $endDate = now();
            $startDate = now()->subDays(7);
            
            $calls = $ringoverService->getCalls([
                'limit_count' => 1000,
                'date_from' => $startDate->timestamp,
                'date_to' => $endDate->timestamp,
            ]);
            
            if (empty($calls)) {
                return [
                    'labels' => [],
                    'datasets' => [],
                ];
            }
            
            $dailyStats = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('d/m');
                $dailyStats[$date] = [
                    'total' => 0,
                    'answered' => 0,
                    'missed' => 0,
                    'incoming' => 0,
                    'outgoing' => 0,
                ];
            }
            
            foreach ($calls as $call) {
                $callDate = \Carbon\Carbon::parse($call['creation_time'] ?? now())->format('d/m');
                
                if (isset($dailyStats[$callDate])) {
                    $dailyStats[$callDate]['total']++;
                    
                    if ($call['is_answered'] ?? false) {
                        $dailyStats[$callDate]['answered']++;
                    } else {
                        $dailyStats[$callDate]['missed']++;
                    }
                    
                    if (($call['direction'] ?? '') === 'inbound') {
                        $dailyStats[$callDate]['incoming']++;
                    } else {
                        $dailyStats[$callDate]['outgoing']++;
                    }
                }
            }
            
            return [
                'labels' => array_keys($dailyStats),
                'datasets' => [
                    [
                        'label' => 'Appels répondus',
                        'data' => array_column($dailyStats, 'answered'),
                        'borderColor' => 'rgb(34, 197, 94)',
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Appels manqués',
                        'data' => array_column($dailyStats, 'missed'),
                        'borderColor' => 'rgb(239, 68, 68)',
                        'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Entrants',
                        'data' => array_column($dailyStats, 'incoming'),
                        'borderColor' => 'rgb(59, 130, 246)',
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'fill' => true,
                    ],
                    [
                        'label' => 'Sortants',
                        'data' => array_column($dailyStats, 'outgoing'),
                        'borderColor' => 'rgb(168, 85, 247)',
                        'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                        'fill' => true,
                    ],
                ],
            ];
            
        } catch (\Exception $exception) {
            Log::error('RingoverCallsTrendWidget error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            
            $this->errorMessage = 'Impossible de charger les tendances d\'appels';
            
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
            'elements' => [
                'line' => [
                    'tension' => 0.4,
                ],
                'point' => [
                    'radius' => 4,
                    'hoverRadius' => 6,
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRoleCache('admin') || $user->hasRoleCache('superviseur'));
    }
}
