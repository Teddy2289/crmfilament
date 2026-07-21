<?php

namespace App\Filament\NsConseil\Widgets;

use App\Models\RendezVous;
use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class DirectionRdvParDepartementChart extends ChartWidget
{
    protected static ?string $heading = 'RDV par commercial — Ce mois';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected static ?string $pollingInterval = '300s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasRoleCache('admin')
                || $user->isSuperAdmin());
    }

    protected function getData(): array
    {
        $data = RendezVous::query()
            ->select('commercial_id', DB::raw('COUNT(*) as total'))
            ->whereMonth('date_heure', now()->month)
            ->whereYear('date_heure', now()->year)
            ->whereNotNull('commercial_id')
            ->groupBy('commercial_id')
            ->orderByDesc('total')
            ->get();

        $labels = $data->map(function ($row) {
            $user = User::find($row->commercial_id);

            return $user ? trim("{$user->prenom} {$user->nom}") : 'Inconnu';
        })->toArray();

        $colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
            '#06b6d4', '#f97316', '#84cc16', '#ec4899', '#14b8a6',
        ];

        return [
            'datasets' => [
                [
                    'label' => 'RDV',
                    'data' => $data->pluck('total')->toArray(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                    'borderColor' => array_slice($colors, 0, $data->count()),
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
