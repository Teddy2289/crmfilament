<?php

namespace App\Console\Commands;

use App\Models\CampagnePhoning;
use App\Models\CrmCampaignWeeklyReport;
use App\Models\Prospect;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WeeklyCampaignReport extends Command
{
    protected $signature = 'crm:weekly-campaign-report
        {--department= : Département à analyser, par exemple 31}
        {--date= : Date du rapport au format YYYY-MM-DD}
        {--dry-run : Calculer et afficher sans enregistrer}
        {--output= : Écrire un CSV à ce chemin}
        {--graph= : Écrire un graphique SVG des statuts à ce chemin}';

    protected $description = 'Génère un instantané hebdomadaire des statuts et des compteurs d’appels des campagnes';

    public function handle(): int
    {
        try {
            $reportDate = $this->option('date')
                ? CarbonImmutable::parse((string) $this->option('date'))->startOfDay()
                : CarbonImmutable::now()->startOfDay();
        } catch (\Throwable) {
            $this->error('La date doit être au format YYYY-MM-DD.');
            return self::INVALID;
        }

        $department = $this->option('department') !== null ? trim((string) $this->option('department')) : null;
        if ($department !== null && ! preg_match('/^\d{2,3}$/', $department)) {
            $this->error('Le département doit contenir 2 ou 3 chiffres.');
            return self::INVALID;
        }

        $rows = $this->buildRows($reportDate, $department);
        if ($rows === []) {
            $this->warn('Aucune campagne départementale trouvée pour ce périmètre.');
            return self::SUCCESS;
        }

        $this->table(
            ['Dépt.', 'Campagne', 'Total dépt.', 'Ciblés', 'Disponibles', 'Refroidissement', 'Max tentatives', 'Traités', 'Restants'],
            collect($rows)->map(fn (array $row): array => [
                $row['department'], $row['campaign_name'], $row['total_department'], $row['total_targeted'],
                $row['total_available'], $row['cooling_down'], $row['max_attempts_reached'], $row['treated'], $row['remaining'],
            ])->all()
        );

        if ($this->option('output')) {
            $this->writeCsv((string) $this->option('output'), $rows);
            $this->info('CSV écrit : '.$this->option('output'));
        }
        if ($this->option('graph')) {
            if (count($rows) > 1) {
                $this->warn('Le graphique utilise la première campagne du périmètre. Utilisez --department pour un graphique par département.');
            }
            $this->writeGraph((string) $this->option('graph'), $rows[0]);
            $this->info('Graphique SVG écrit : '.$this->option('graph'));
        }

        if ($this->option('dry-run')) {
            $this->line('Dry-run : aucun instantané n’a été enregistré.');
            return self::SUCCESS;
        }

        foreach ($rows as $row) {
            CrmCampaignWeeklyReport::updateOrCreate(
                ['report_date' => $reportDate->toDateString(), 'scope' => 'campaign', 'department' => $row['department'], 'campaign_id' => $row['campaign_id']],
                [
                    'total_department' => $row['total_department'], 'total_targeted' => $row['total_targeted'],
                    'total_available' => $row['total_available'], 'cooling_down' => $row['cooling_down'],
                    'max_attempts_reached' => $row['max_attempts_reached'], 'without_phone' => $row['without_phone'],
                    'treated' => $row['treated'], 'remaining' => $row['remaining'],
                    'status_breakdown' => $row['status_breakdown'], 'status_trends' => $row['status_trends'],
                    'campaign_breakdown' => [$row['campaign_id'] => $row['campaign_name']], 'comparison' => $row['comparison'],
                ]
            );
        }

        $this->info('Instantané enregistré pour le '.$reportDate->format('d/m/Y').'.');
        return self::SUCCESS;
    }

    private function buildRows(CarbonImmutable $reportDate, ?string $department): array
    {
        $campaigns = CampagnePhoning::query()
            ->where('type_entite', 'prospects')
            ->when($department !== null, fn ($q) => $q->where('criteres->departement', $department))
            ->orderBy('id')->get();
        $rows = [];

        foreach ($campaigns as $campaign) {
            $criteria = is_array($campaign->criteres) ? $campaign->criteres : [];
            $dept = (string) ($criteria['departement'] ?? '');
            if ($dept === '') continue;
            $base = Prospect::query()->whereNull('deleted_at')->where('departement', $dept);
            $totalDepartment = (clone $base)->count();
            $withoutPhone = (clone $base)->whereNull('telephone')->count();
            $statusBreakdown = (clone $base)->select('statut', DB::raw('COUNT(*) as total'))->groupBy('statut')->orderBy('statut')
                ->pluck('total', 'statut')->mapWithKeys(fn ($count, $status): array => [(string) $status => (int) $count])->all();
            $targeted = $campaign->buildQuery()->count();
            $available = $campaign->countQueueContacts();
            $coolingDown = 0; // Refroidissement désactivé.
            $maxAttempts = (clone $base)->whereHas('appels', fn ($q) => $q->where('compte_comme_tentative', true), '>=', (int) ($campaign->max_tentatives ?: 4))->count();
            $stats = $campaign->getStats();
            $totalUniqueCalled = (int) ($stats['contacts_uniques_appeles'] ?? 0);
            $totalCalls = (int) ($stats['total_appels'] ?? 0);
            $previous = CrmCampaignWeeklyReport::query()->where('campaign_id', $campaign->id)->where('report_date', '<', $reportDate->toDateString())->latest('report_date')->first();
            $row = [
                'department' => $dept, 'campaign_id' => $campaign->id, 'campaign_name' => $campaign->nom,
                'total_department' => $totalDepartment, 'total_targeted' => $targeted, 'total_available' => $available,
                'total_unique_called' => $totalUniqueCalled, 'total_calls' => $totalCalls,
                'cooling_down' => $coolingDown, 'max_attempts_reached' => $maxAttempts, 'without_phone' => $withoutPhone,
                'treated' => (int) ($stats['contacts_traites'] ?? 0), 'remaining' => (int) ($stats['contacts_restants'] ?? 0),
                'status_breakdown' => $statusBreakdown, 'status_trends' => [],
                'comparison' => $previous ? ['previous_report_date' => $previous->report_date?->toDateString(), 'available_delta' => $available - $previous->total_available, 'targeted_delta' => $targeted - $previous->total_targeted] : null,
            ];
            $row['status_trends'] = $this->buildStatusTrends($reportDate, $campaign->id, $statusBreakdown);
            $rows[] = $row;
        }
        return $rows;
    }

    private function buildStatusTrends(CarbonImmutable $reportDate, int $campaignId, array $current): array
    {
        $dates = collect(range(3, 0))->map(fn (int $weeks): string => $reportDate->subWeeks($weeks)->toDateString())->push($reportDate->toDateString())->unique()->values();
        $snapshots = CrmCampaignWeeklyReport::query()->where('campaign_id', $campaignId)->whereIn('report_date', $dates->all())->get()->keyBy(fn ($r): string => $r->report_date->toDateString());
        $snapshots->put($reportDate->toDateString(), (object) ['status_breakdown' => $current]);
        $statuses = collect($current)->keys();
        foreach ($snapshots as $snapshot) $statuses = $statuses->merge(array_keys($snapshot->status_breakdown ?? []));
        $result = ['labels' => $dates->all(), 'series' => []];
        foreach ($statuses->unique()->sort()->values() as $status) {
            $result['series'][(string) $status] = $dates->map(fn (string $date): int => (int) (($snapshots->get($date)?->status_breakdown ?? [])[$status] ?? 0))->all();
        }
        return $result;
    }

    private function writeCsv(string $path, array $rows): void
    {
        $parent = dirname($path);
        if (! is_dir($parent) && ! @mkdir($parent, 0755, true) && ! is_dir($parent)) throw new \RuntimeException('Dossier CSV inaccessible : '.$parent);
        $handle = fopen($path, 'wb');
        fputcsv($handle, ['Département', 'Campagne', 'Prospects réels', 'Ciblés', 'Disponibles', 'Contacts uniques appelés', 'Appels passés', 'Refroidissement', 'Max tentatives', 'Sans téléphone', 'Traités', 'Restants', 'Statuts']);
        foreach ($rows as $row) fputcsv($handle, [$row['department'], $row['campaign_name'], $row['total_department'], $row['total_targeted'], $row['total_available'], $row['total_unique_called'], $row['total_calls'], $row['cooling_down'], $row['max_attempts_reached'], $row['without_phone'], $row['treated'], $row['remaining'], json_encode($row['status_breakdown'], JSON_UNESCAPED_UNICODE)]);
        fclose($handle);
    }

    private function writeGraph(string $path, array $row): void
    {
        $parent = dirname($path);
        if (! is_dir($parent) && ! @mkdir($parent, 0755, true) && ! is_dir($parent)) throw new \RuntimeException('Dossier graphique inaccessible : '.$parent);
        $trend = $row['status_trends']; $labels = $trend['labels']; $series = $trend['series'];
        $width = 1000; $height = 560; $left = 90; $top = 60; $right = 35; $bottom = 100; $plotW = $width - $left - $right; $plotH = $height - $top - $bottom;
        $max = max(1, ...array_values(array_map(fn ($values): int => max($values ?: [0]), $series)));
        $colors = ['#2563eb','#16a34a','#dc2626','#ca8a04','#9333ea','#0891b2','#ea580c','#4f46e5'];
        $esc = fn ($v): string => htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'"><rect width="100%" height="100%" fill="#ffffff"/><text x="'.$left.'" y="30" font-family="Arial" font-size="20" font-weight="bold">Évolution des statuts — département '.$esc($row['department']).'</text>';
        for ($i = 0; $i <= 4; $i++) { $y = $top + ($plotH * $i / 4); $value = (int) round($max * (4 - $i) / 4); $svg .= '<line x1="'.$left.'" y1="'.$y.'" x2="'.($left + $plotW).'" y2="'.$y.'" stroke="#e5e7eb"/><text x="'.($left - 12).'" y="'.($y + 5).'" text-anchor="end" font-family="Arial" font-size="12" fill="#4b5563">'.$value.'</text>'; }
        foreach ($labels as $i => $label) { $x = $left + ($plotW * ($i / max(1, count($labels) - 1))); $svg .= '<text x="'.$x.'" y="'.($top + $plotH + 28).'" text-anchor="middle" font-family="Arial" font-size="12" fill="#4b5563">'.$esc($label).'</text>'; }
        $index = 0; $legendX = $left; $legendY = $height - 42;
        foreach ($series as $status => $values) { $color = $colors[$index % count($colors)]; $points = []; foreach ($values as $i => $value) { $x = $left + ($plotW * ($i / max(1, count($labels) - 1))); $y = $top + $plotH - ($plotH * $value / $max); $points[] = $x.','.$y; $svg .= '<circle cx="'.$x.'" cy="'.$y.'" r="4" fill="'.$color.'"/>'; } $svg .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="'.$color.'" stroke-width="3"/><rect x="'.$legendX.'" y="'.($legendY - 12).'" width="12" height="12" fill="'.$color.'"/><text x="'.($legendX + 18).'" y="'.$legendY.'" font-family="Arial" font-size="12">'.$esc($status).'</text>'; $legendX += 105; $index++; }
        $svg .= '</svg>'; file_put_contents($path, $svg);
    }
}
