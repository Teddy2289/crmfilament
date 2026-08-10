<?php

namespace App\Services;

use App\Models\Report;
use App\Models\Prospect;
use App\Models\Client;
use App\Models\Partenaire;
use App\Models\Opportunite;
use App\Models\RendezVous;
use App\Models\Appel;
use Illuminate\Support\Facades\DB;

class ReportGeneratorService
{
    public function generate(Report $report): array
    {
        $config = $report->config;
        $model = $this->getModel($config['model']);
        
        $query = $model::query();
        
        // Appliquer les filtres
        if (isset($config['filters']) && is_array($config['filters'])) {
            foreach ($config['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }
        
        // Sélectionner les colonnes
        $columns = $config['columns'] ?? ['*'];
        $query->select($columns);
        
        // Grouper si nécessaire
        if (isset($config['group_by'])) {
            $query->groupBy($config['group_by']);
        }
        
        $data = $query->get();
        
        return [
            'report' => $report,
            'data' => $data,
            'config' => $config,
            'total' => $data->count(),
        ];
    }
    
    protected function getModel(string $modelName): string
    {
        return match($modelName) {
            'prospect' => Prospect::class,
            'client' => Client::class,
            'partenaire' => Partenaire::class,
            'opportunite' => Opportunite::class,
            'rendez_vous' => RendezVous::class,
            'appel' => Appel::class,
            default => Prospect::class,
        };
    }
    
    public function generateStatistics(Report $report): array
    {
        $config = $report->config;
        $model = $this->getModel($config['model']);
        
        $query = $model::query();
        
        // Appliquer les filtres
        if (isset($config['filters']) && is_array($config['filters'])) {
            foreach ($config['filters'] as $field => $value) {
                $query->where($field, $value);
            }
        }
        
        $total = $query->count();
        
        // Statistiques par statut si disponible
        $stats = [];
        if (in_array('statut', $config['columns'] ?? [])) {
            $stats['by_status'] = $query->select('statut', DB::raw('count(*) as count'))
                ->groupBy('statut')
                ->pluck('count', 'statut')
                ->toArray();
        }
        
        return [
            'total' => $total,
            'stats' => $stats,
        ];
    }
}
