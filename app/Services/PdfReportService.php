<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;

class PdfReportService
{
    public function generateReport(string $template, array $data, string $filename): \Illuminate\Http\Response
    {
        $pdf = Pdf::loadView($template, $data);
        
        return $pdf->download($filename);
    }

    public function generateProspectReport($prospect): \Illuminate\Http\Response
    {
        $data = [
            'prospect' => $prospect,
            'date' => now()->format('d/m/Y'),
        ];

        return $this->generateReport('pdf.prospect-report', $data, "prospect-{$prospect->id}-report.pdf");
    }

    public function generateClientReport($client): \Illuminate\Http\Response
    {
        $data = [
            'client' => $client,
            'date' => now()->format('d/m/Y'),
        ];

        return $this->generateReport('pdf.client-report', $data, "client-{$client->id}-report.pdf");
    }

    public function generatePartenaireReport($partenaire): \Illuminate\Http\Response
    {
        $data = [
            'partenaire' => $partenaire,
            'date' => now()->format('d/m/Y'),
        ];

        return $this->generateReport('pdf.partenaire-report', $data, "partenaire-{$partenaire->id}-report.pdf");
    }

    public function generateDossierFormationReport($dossier): \Illuminate\Http\Response
    {
        $data = [
            'dossier' => $dossier,
            'date' => now()->format('d/m/Y'),
        ];

        return $this->generateReport('pdf.dossier-formation-report', $data, "dossier-{$dossier->id}-report.pdf");
    }

    public function generateActivitiesReport($startDate, $endDate, $userId = null): \Illuminate\Http\Response
    {
        $query = \App\Models\ActiviteVente::query()
            ->whereBetween('derniere_vente', [$startDate, $endDate]);
        
        if ($userId) {
            $query->where('consultant_id', $userId);
        }

        $activities = $query->get();

        $data = [
            'activities' => $activities,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'date' => now()->format('d/m/Y'),
        ];

        return $this->generateReport('pdf.activities-report', $data, "activities-report-{$startDate}-{$endDate}.pdf");
    }

    public function generateCustomReport(string $view, array $data, string $filename): \Illuminate\Http\Response
    {
        return $this->generateReport($view, $data, $filename);
    }
}
