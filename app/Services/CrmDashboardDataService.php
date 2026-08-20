<?php

namespace App\Services;

use App\Models\Client;
use App\Models\HistoriqueInteractionUser;
use App\Models\Opportunite;
use App\Models\Partenaire;
use App\Models\Prospect;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class CrmDashboardDataService
{
    public function getData(?string $period = null, ?string $startDate = null, ?string $endDate = null): array
    {
        [$from, $to, $periodLabel] = $this->resolvePeriod($period, $startDate, $endDate);
        [$previousFrom, $previousTo] = $this->resolvePreviousPeriod($from, $to);
        $periodMeta = [
            'key' => $period ?: 'all',
            'label' => $periodLabel,
            'start' => $from?->toDateString(),
            'end' => $to?->toDateString(),
            'previous_start' => $previousFrom?->toDateString(),
            'previous_end' => $previousTo?->toDateString(),
        ];

        $empty = [
            'period' => $periodMeta,
            'kpis' => [],
            'comparisons' => [],
            'pipeline' => [],
            'pipeline_trend' => [],
            'activities' => [],
            'actions' => [],
        ];

        try {
            $pipelineQuery = Prospect::query();
            $this->applyPeriod($pipelineQuery, 'created_at', $from, $to);
            $pipeline = $pipelineQuery
                ->selectRaw("COALESCE(statut, 'Sans statut') as label, COUNT(*) as total")
                ->groupBy('statut')
                ->orderByDesc('total')
                ->limit(8)
                ->get()
                ->map(fn ($row) => ['label' => (string) $row->label, 'total' => (int) $row->total])
                ->values()
                ->all();

            $activitiesQuery = HistoriqueInteractionUser::query()
                ->with('user:id,nom,prenom')
                ->latest('date_interaction');
            $this->applyPeriod($activitiesQuery, 'date_interaction', $from, $to);
            $activities = $activitiesQuery
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'date' => optional($row->date_interaction)->format('d/m/Y H:i'),
                    'user' => (string) optional($row->user)->name,
                    'type' => is_object($row->type_interaction ?? null) ? (($row->type_interaction->label ?? null) ?: ($row->type_interaction->value ?? null) ?: ($row->type_interaction->name ?? 'Activité')) : (string) ($row->type_interaction_label ?? $row->type_interaction ?? 'Activité'),
                    'description' => (string) ($row->description ?? ''),
                ])
                ->values()
                ->all();

            $actionsQuery = Prospect::query()->whereIn('statut', ['AC', 'RP'])->latest('updated_at');
            $this->applyPeriod($actionsQuery, 'updated_at', $from, $to);
            $actions = $actionsQuery
                ->limit(8)
                ->get(['id', 'nom', 'statut', 'updated_at'])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => (string) ($row->nom ?? 'Prospect sans nom'),
                    'status' => is_object($row->statut ?? null) ? (($row->statut->label ?? null) ?: ($row->statut->value ?? null) ?: ($row->statut->name ?? '—')) : (string) ($row->statut ?? '—'),
                    'updated' => optional($row->updated_at)->format('d/m/Y H:i'),
                    'url' => url('/ns-conseil/prospects/'.$row->id),
                ])
                ->values()
                ->all();

            $kpiDefinitions = [
                ['key' => 'prospects', 'label' => $from ? 'Prospects créés' : 'Prospects actifs', 'tone' => 'blue', 'model' => Prospect::class],
                ['key' => 'clients', 'label' => $from ? 'Clients créés' : 'Clients', 'tone' => 'emerald', 'model' => Client::class],
                ['key' => 'partenaires', 'label' => $from ? 'Partenaires créés' : 'Partenaires', 'tone' => 'violet', 'model' => Partenaire::class],
                ['key' => 'opportunites', 'label' => $from ? 'Opportunités créées' : 'Opportunités', 'tone' => 'amber', 'model' => Opportunite::class],
            ];
            $kpis = [];
            $comparisons = [];
            foreach ($kpiDefinitions as $definition) {
                $current = $this->countInPeriod($definition['model'], 'created_at', $from, $to);
                $previous = $previousFrom ? $this->countInPeriod($definition['model'], 'created_at', $previousFrom, $previousTo) : null;
                $comparison = $this->comparison($current, $previous);
                $kpis[] = ['key' => $definition['key'], 'label' => $definition['label'], 'value' => $current, 'tone' => $definition['tone'], 'comparison' => $comparison];
                $comparisons[$definition['key']] = $comparison;
            }

            return [
                'period' => $periodMeta,
                'kpis' => $kpis,
                'comparisons' => $comparisons,
                'pipeline' => $pipeline,
                'pipeline_trend' => $this->pipelineTrend($from, $to),
                'activities' => $activities,
                'actions' => $actions,
            ];
        } catch (\Throwable $exception) {
            Log::error('CrmDashboardDataService failed', ['message' => $exception->getMessage(), 'exception' => $exception]);
            return $empty;
        }
    }

    private function pipelineTrend(?Carbon $from, ?Carbon $to): array
    {
        $trendFrom = $from?->copy() ?: now()->subDays(29)->startOfDay();
        $trendTo = $to?->copy() ?: now()->endOfDay();
        $days = max(1, $trendFrom->diffInDays($trendTo) + 1);
        $bucket = $days > 62 ? 'week' : 'day';
        $expression = $bucket === 'week'
            ? "DATE_FORMAT(created_at, '%x-W%v')"
            : "DATE_FORMAT(created_at, '%Y-%m-%d')";

        return Prospect::query()
            ->whereBetween('created_at', [$trendFrom, $trendTo])
            ->selectRaw("{$expression} as bucket, COALESCE(statut, 'Sans statut') as label, COUNT(*) as total")
            ->groupByRaw("{$expression}, statut")
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row) => [
                'bucket' => (string) $row->bucket,
                'label' => (string) $row->label,
                'total' => (int) $row->total,
            ])
            ->values()
            ->all();
    }

    private function comparison(int $current, ?int $previous): ?array
    {
        if ($previous === null) return null;
        $delta = $current - $previous;
        return [
            'previous' => $previous,
            'delta' => $delta,
            'percent' => $previous === 0 ? ($current === 0 ? 0 : null) : round(($delta / $previous) * 100, 1),
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'flat'),
        ];
    }

    private function resolvePreviousPeriod(?Carbon $from, ?Carbon $to): array
    {
        if (!$from || !$to) return [null, null];
        $duration = $from->diffInSeconds($to) + 1;
        $previousTo = $from->copy()->subSecond();
        return [$previousTo->copy()->subSeconds($duration - 1), $previousTo];
    }

    private function countInPeriod(string $model, string $column, ?Carbon $from, ?Carbon $to): int
    {
        $query = $model::query();
        $this->applyPeriod($query, $column, $from, $to);
        return (int) $query->count();
    }

    private function applyPeriod(Builder $query, string $column, ?Carbon $from, ?Carbon $to): void
    {
        if ($from) $query->where($column, '>=', $from);
        if ($to) $query->where($column, '<=', $to);
    }

    private function resolvePeriod(?string $period, ?string $startDate, ?string $endDate): array
    {
        $period = in_array($period, ['today', '7d', '30d', 'custom'], true) ? $period : 'all';
        $now = now();
        if ($period === 'today') return [$now->copy()->startOfDay(), $now->copy()->endOfDay(), "Aujourd’hui"];
        if ($period === '7d') return [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), '7 derniers jours'];
        if ($period === '30d') return [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), '30 derniers jours'];
        if ($period === 'custom') {
            $from = $this->parseDate($startDate, false);
            $to = $this->parseDate($endDate, true);
            if ($from && $to && $from->lte($to)) return [$from, $to, 'Période personnalisée'];
        }
        return [null, null, 'Toutes les périodes'];
    }

    private function parseDate(?string $value, bool $endOfDay): ?Carbon
    {
        if (!$value || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return null;
        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}

