<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Opportunite;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getConversionFunnel($startDate, $endDate): array
    {
        return [
            [
                'stage' => 'Prospects',
                'count' => Prospect::whereBetween('created_at', [$startDate, $endDate])->count(),
                'color' => '#6366f1',
            ],
            [
                'stage' => 'Contacts établis',
                'count' => Prospect::whereBetween('created_at', [$startDate, $endDate])
                    ->whereNotNull('date_premier_contact')
                    ->count(),
                'color' => '#8b5cf6',
            ],
            [
                'stage' => 'RDV planifiés',
                'count' => Prospect::whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('rendezVous')
                    ->count(),
                'color' => '#a855f7',
            ],
            [
                'stage' => 'Opportunités',
                'count' => Opportunite::whereBetween('created_at', [$startDate, $endDate])->count(),
                'color' => '#d946ef',
            ],
            [
                'stage' => 'Partenaires',
                'count' => Partenaire::whereBetween('created_at', [$startDate, $endDate])->count(),
                'color' => '#ec4899',
            ],
            [
                'stage' => 'Clients',
                'count' => Client::whereBetween('created_at', [$startDate, $endDate])->count(),
                'color' => '#f43f5e',
            ],
        ];
    }

    public function getYearOverYearComparison($metric, $currentYear): array
    {
        $previousYear = $currentYear - 1;

        $currentYearData = $this->getMonthlyData($metric, $currentYear);
        $previousYearData = $this->getMonthlyData($metric, $previousYear);

        return [
            'current_year' => $currentYear,
            'previous_year' => $previousYear,
            'current_data' => $currentYearData,
            'previous_data' => $previousYearData,
            'growth_rate' => $this->calculateGrowthRate($currentYearData, $previousYearData),
        ];
    }

    private function getMonthlyData($metric, $year): array
    {
        $data = [];
        for ($month = 1; $month <= 12; $month++) {
            $data[] = $this->getMetricForMonth($metric, $year, $month);
        }
        return $data;
    }

    private function getMetricForMonth($metric, $year, $month): int
    {
        return match($metric) {
            'prospects' => Prospect::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count(),
            'clients' => Client::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count(),
            'partenaires' => Partenaire::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count(),
            'opportunites' => Opportunite::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->count(),
            default => 0,
        };
    }

    private function calculateGrowthRate($currentData, $previousData): float
    {
        $currentTotal = array_sum($currentData);
        $previousTotal = array_sum($previousData);

        if ($previousTotal === 0) {
            return $currentTotal > 0 ? 100 : 0;
        }

        return (($currentTotal - $previousTotal) / $previousTotal) * 100;
    }

    public function getGeographicHeatmap(): array
    {
        return DB::table('prospects')
            ->select('region', DB::raw('count(*) as count'))
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderByDesc('count')
            ->get()
            ->map(function ($item) {
                return [
                    'region' => $item->region,
                    'count' => $item->count,
                ];
            })
            ->toArray();
    }

    public function getCustomKpi($kpiConfig): array
    {
        $metric = $kpiConfig['metric'];
        $period = $kpiConfig['period'] ?? 'month';
        $startDate = $this->getStartDateForPeriod($period);
        $endDate = now();

        return [
            'label' => $kpiConfig['label'],
            'value' => $this->getMetricValue($metric, $startDate, $endDate),
            'target' => $kpiConfig['target'] ?? null,
            'progress' => $this->calculateProgress($metric, $startDate, $endDate, $kpiConfig['target'] ?? null),
        ];
    }

    private function getStartDateForPeriod($period): \Carbon\Carbon
    {
        return match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };
    }

    private function getMetricValue($metric, $startDate, $endDate): int
    {
        return match($metric) {
            'prospects_created' => Prospect::whereBetween('created_at', [$startDate, $endDate])->count(),
            'clients_created' => Client::whereBetween('created_at', [$startDate, $endDate])->count(),
            'partenaires_created' => Partenaire::whereBetween('created_at', [$startDate, $endDate])->count(),
            'opportunites_created' => Opportunite::whereBetween('created_at', [$startDate, $endDate])->count(),
            'tasks_completed' => Task::whereBetween('date_realisation', [$startDate, $endDate])
                ->where('statut', 'terminee')
                ->count(),
            'opportunites_won' => Opportunite::whereBetween('date_cloture', [$startDate, $endDate])
                ->where('statut', 'gagnee')
                ->count(),
            default => 0,
        };
    }

    private function calculateProgress($metric, $startDate, $endDate, $target): float
    {
        if (!$target) {
            return 0;
        }

        $currentValue = $this->getMetricValue($metric, $startDate, $endDate);
        return min(($currentValue / $target) * 100, 100);
    }

    public function getDetailedPeriodStats($startDate, $endDate): array
    {
        return [
            'prospects' => [
                'total' => Prospect::whereBetween('created_at', [$startDate, $endDate])->count(),
                'converted' => Prospect::whereBetween('created_at', [$startDate, $endDate])
                    ->whereHas('client')
                    ->count(),
                'conversion_rate' => $this->getConversionRate('prospect', $startDate, $endDate),
            ],
            'clients' => [
                'total' => Client::whereBetween('created_at', [$startDate, $endDate])->count(),
                'active' => Client::whereBetween('created_at', [$startDate, $endDate])
                    ->where('statut', 'actif')
                    ->count(),
            ],
            'opportunites' => [
                'total' => Opportunite::whereBetween('created_at', [$startDate, $endDate])->count(),
                'won' => Opportunite::whereBetween('created_at', [$startDate, $endDate])
                    ->where('statut', 'gagnee')
                    ->count(),
                'lost' => Opportunite::whereBetween('created_at', [$startDate, $endDate])
                    ->where('statut', 'perdue')
                    ->count(),
                'win_rate' => $this->getWinRate($startDate, $endDate),
            ],
            'tasks' => [
                'total' => Task::whereBetween('created_at', [$startDate, $endDate])->count(),
                'completed' => Task::whereBetween('created_at', [$startDate, $endDate])
                    ->where('statut', 'terminee')
                    ->count(),
                'overdue' => Task::where('date_echeance', '<', now())
                    ->whereNotIn('statut', ['terminee', 'annulee'])
                    ->count(),
            ],
        ];
    }

    private function getConversionRate($type, $startDate, $endDate): float
    {
        $total = Prospect::whereBetween('created_at', [$startDate, $endDate])->count();
        $converted = Prospect::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('client')
            ->count();

        if ($total === 0) {
            return 0;
        }

        return ($converted / $total) * 100;
    }

    private function getWinRate($startDate, $endDate): float
    {
        $total = Opportunite::whereBetween('created_at', [$startDate, $endDate])->count();
        $won = Opportunite::whereBetween('created_at', [$startDate, $endDate])
            ->where('statut', 'gagnee')
            ->count();

        if ($total === 0) {
            return 0;
        }

        return ($won / $total) * 100;
    }
}
