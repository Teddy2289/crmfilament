<?php

namespace App\Filament\NsConseil\Widgets;

use App\Enums\OrganizationStatus;
use App\Models\Partenaire;
use Filament\Widgets\ChartWidget;

class PipelinePartenairesWidget extends ChartWidget
{
    protected static ?string $heading = 'Pipeline Partenaires — Répartition par statut';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $statuts = OrganizationStatus::cases();
        $counts = [];
        $colors = [
            'a_prospecter' => '#94a3b8',
            'en_cours_prospection' => '#3b82f6',
            'rdv_en_cours' => '#f59e0b',
            'signe_accord_cadre' => '#10b981',
            'convention_engagement' => '#059669',
            'refus' => '#ef4444',
        ];

        $labels = [];
        foreach ($statuts as $statut) {
            $counts[] = Partenaire::query()->where('statut', $statut->value)->count();
            $labels[] = $statut->label();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Partenaires',
                    'data' => $counts,
                    'backgroundColor' => array_values($colors),
                    'borderColor' => array_values($colors),
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
