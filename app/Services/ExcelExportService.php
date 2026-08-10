<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProspectsExport;
use App\Exports\ClientsExport;
use App\Exports\PartenairesExport;
use App\Exports\OpportunitesExport;
use App\Exports\RendezVousExport;
use App\Exports\AppelsExport;

class ExcelExportService
{
    /**
     * Exporter les prospects
     */
    public function exportProspects($filters = [])
    {
        return Excel::download(new ProspectsExport($filters), 'prospects-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /**
     * Exporter les clients
     */
    public function exportClients($filters = [])
    {
        return Excel::download(new ClientsExport($filters), 'clients-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /**
     * Exporter les partenaires
     */
    public function exportPartenaires($filters = [])
    {
        return Excel::download(new PartenairesExport($filters), 'partenaires-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /**
     * Exporter les opportunités
     */
    public function exportOpportunites($filters = [])
    {
        return Excel::download(new OpportunitesExport($filters), 'opportunites-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /**
     * Exporter les rendez-vous
     */
    public function exportRendezVous($filters = [])
    {
        return Excel::download(new RendezVousExport($filters), 'rendez-vous-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /**
     * Exporter les appels
     */
    public function exportAppels($filters = [])
    {
        return Excel::download(new AppelsExport($filters), 'appels-' . now()->format('Y-m-d-His') . '.xlsx');
    }

    /**
     * Exporter en masse plusieurs entités
     */
    public function exportMultiple($entities, $filters = [])
    {
        $exports = [];

        foreach ($entities as $entity) {
            switch ($entity) {
                case 'prospects':
                    $exports[] = new ProspectsExport($filters);
                    break;
                case 'clients':
                    $exports[] = new ClientsExport($filters);
                    break;
                case 'partenaires':
                    $exports[] = new PartenairesExport($filters);
                    break;
                case 'opportunites':
                    $exports[] = new OpportunitesExport($filters);
                    break;
                case 'rendez-vous':
                    $exports[] = new RendezVousExport($filters);
                    break;
                case 'appels':
                    $exports[] = new AppelsExport($filters);
                    break;
            }
        }

        return Excel::download($exports, 'export-multiple-' . now()->format('Y-m-d-His') . '.xlsx');
    }
}
