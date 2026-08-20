<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class ProspectionStatutsChart extends ChartWidget
{
    use InteractsWithPageFilters;

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

        $filterStart = data_get($this->filters, 'startDate')
            ?? data_get($this->filters, 'start_date')
            ?? request()->input('filters.startDate')
            ?? request()->input('filters.start_date');
        $filterEnd = data_get($this->filters, 'endDate')
            ?? data_get($this->filters, 'end_date')
            ?? request()->input('filters.endDate')
            ?? request()->input('filters.end_date');

        $startDate = filled($filterStart)
            ? Carbon::parse($filterStart)->startOfDay()
            : now()->startOfMonth();
        $endDate = filled($filterEnd)
            ? Carbon::parse($filterEnd)->endOfDay()
            : now()->endOfMonth();

        $selectedUserId = data_get($this->filters, 'userId')
            ?? data_get($this->filters, 'user_id')
            ?? request()->input('filters.userId')
            ?? request()->input('filters.user_id');

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
            $query = Prospect::where('statut', $statut->value)
                // La répartition filtrée représente les prospects dont la fiche
                // a été mise à jour pendant la période demandée.
                ->whereBetween('updated_at', [$startDate, $endDate]);

            if ($isTp) {
                $query->where('teleprospecteur_id', $user->id);
            }

            if (filled($selectedUserId)) {
                $query->where('teleprospecteur_id', $selectedUserId);
            }

            $counts[] = $query->count();
            $labels[] = $statut->label();
            $bgColors[] = $colors[$statut->value] ?? '#6b7280';
        }

        return [
            'datasets' => [
                [
                    'label' => 'Prospects du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y'),
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
