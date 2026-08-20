<?php

namespace App\Filament\NsConseil\Widgets\Crm;

use App\Models\Opportunite;
use App\Models\Prospect;
use App\Models\Client;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Log;

class CrmPipelineWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';



    protected static ?string $maxHeight = '400px';

    public ?string $errorMessage = null;

    public function getHeading(): string
    {
        return '📈 Pipeline de Conversion';
    }

    protected function getData(): array
    {
        try {
            $user = auth()->user();
            
            // Base queries with user filtering
            $opportunitesQuery = Opportunite::query();
            $prospectsQuery = Prospect::query();
            $clientsQuery = Client::query();
            
            // Filter by user if not admin/supervisor
            if (!$user->hasRoleCache(['admin', 'superviseur']) && !$user->isSuperAdmin()) {
                if ($user->hasRoleCache('teleprospecteur')) {
                    $prospectsQuery->where('teleprospecteur_id', $user->id);
                    $opportunitesQuery->where('assigne_a', $user->id);
                }
                if ($user->hasRoleCache('commercial')) {
                    $prospectsQuery->where('commercial_id', $user->id);
                    $clientsQuery->where('commercial_id', $user->id);
                }
            }

            // Opportunités par statut
            $oppStats = $opportunitesQuery->clone()
                ->selectRaw('statut, COUNT(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut')
                ->toArray();

            // Prospects par statut
            $prospectStats = $prospectsQuery->clone()
                ->selectRaw('statut, COUNT(*) as total')
                ->groupBy('statut')
                ->pluck('total', 'statut')
                ->toArray();

            // Clients par état
            $clientStats = $clientsQuery->clone()
                ->selectRaw('etat, COUNT(*) as total')
                ->groupBy('etat')
                ->pluck('total', 'etat')
                ->toArray();

            return [
                'datasets' => [
                    [
                        'label' => 'Opportunités',
                        'data' => [
                            $oppStats['nouveau'] ?? 0,
                            $oppStats['en_cours_evaluation'] ?? 0,
                            $oppStats['qualifiee'] ?? 0,
                            $oppStats['converti'] ?? 0,
                        ],
                        'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => 'Prospects',
                        'data' => [
                            $prospectStats['AC'] ?? 0,
                            $prospectStats['RP'] ?? 0,
                            $prospectStats['QF'] ?? 0,
                            $prospectStats['KO'] ?? 0,
                        ],
                        'backgroundColor' => 'rgba(245, 158, 11, 0.7)',
                        'borderColor' => 'rgb(245, 158, 11)',
                        'borderWidth' => 2,
                    ],
                    [
                        'label' => 'Clients',
                        'data' => [
                            $clientStats['prospect'] ?? 0,
                            $clientStats['en_cours'] ?? 0,
                            $clientStats['termine'] ?? 0,
                            $clientStats['certifie'] ?? 0,
                        ],
                        'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                        'borderColor' => 'rgb(16, 185, 129)',
                        'borderWidth' => 2,
                    ],
                ],
                'labels' => ['Nouveau/AC', 'En cours/RP', 'Qualifié/QF', 'Converti/Terminé'],
            ];
            
        } catch (\Exception $exception) {
            Log::error('CrmPipelineWidget error', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
            
            $this->errorMessage = 'Impossible de charger le pipeline de conversion';
            
            return [
                'labels' => [],
                'datasets' => [],
            ];
        }
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'x' => [
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 45,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRoleCache('admin') || $user->hasRoleCache('superviseur') || $user->hasRoleCache('teleprospecteur') || $user->hasRoleCache('commercial'));
    }
}
