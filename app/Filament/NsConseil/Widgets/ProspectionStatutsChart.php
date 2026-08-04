<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Filament\Widgets\ChartWidget;

class ProspectionStatutsChart extends ChartWidget
{
    protected static ?string $heading = 'Répartition des prospects par statut';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '120s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('teleprospecteur')
                || $user->hasRoleCache('superviseur')
                || $user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $isTp = $user->hasRoleCache('teleprospecteur');

        $statuts = ProspectStatut::cases();
        $counts = [];
        $labels = [];
        $colors = [
            'AC' => '#94a3b8',
            'STD_NR' => '#f59e0b',
            'STD_Joint' => '#3b82f6',
            'CSE_NR' => '#f97316',
            'RP' => '#10b981',
            'RPC' => '#059669',
            'KO' => '#ef4444',
            'QF' => '#6366f1',
        ];
        $bgColors = [];

        foreach ($statuts as $statut) {
            $query = Prospect::where('statut', $statut->value);

            if ($isTp) {
                $query->where('teleprospecteur_id', $user->id);
            }

            $counts[] = $query->count();
            $labels[] = $statut->label();
            $bgColors[] = $colors[$statut->value] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Prospects',
                    'data' => $counts,
                    'backgroundColor' => $bgColors,
                    'borderColor' => $bgColors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1],
                ],
            ],
        ];
    }
}
