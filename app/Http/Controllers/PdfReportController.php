<?php

namespace App\Http\Controllers;

use App\Services\PdfExportService;
use Illuminate\Http\Request;

class PdfReportController extends Controller
{
    protected $pdfExportService;

    public function __construct(PdfExportService $pdfExportService)
    {
        $this->pdfExportService = $pdfExportService;
    }

    public function prospectReport($id)
    {
        $prospect = \App\Models\Prospect::findOrFail($id);
        return $this->pdfExportService->generateFromView('pdf.prospect-report', [
            'prospect' => $prospect,
            'date' => now()->format('d/m/Y'),
        ], "prospect-{$prospect->id}-report.pdf");
    }

    public function clientReport($id)
    {
        $client = \App\Models\Client::findOrFail($id);
        return $this->pdfExportService->generateFromView('pdf.client-report', [
            'client' => $client,
            'date' => now()->format('d/m/Y'),
        ], "client-{$client->id}-report.pdf");
    }

    public function partenaireReport($id)
    {
        $partenaire = \App\Models\Partenaire::findOrFail($id);
        return $this->pdfExportService->generateFromView('pdf.partenaire-report', [
            'partenaire' => $partenaire,
            'date' => now()->format('d/m/Y'),
        ], "partenaire-{$partenaire->id}-report.pdf");
    }

    public function dossierFormationReport($id)
    {
        $dossier = \App\Models\DossierFormation::findOrFail($id);
        return $this->pdfExportService->generateFromView('pdf.dossier-formation-report', [
            'dossier' => $dossier,
            'date' => now()->format('d/m/Y'),
        ], "dossier-{$dossier->id}-report.pdf");
    }

    public function activitiesReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $userId = $request->input('user_id');

        $query = \App\Models\ActiviteVente::query()
            ->whereBetween('derniere_vente', [$startDate, $endDate]);
        
        if ($userId) {
            $query->where('consultant_id', $userId);
        }

        $activities = $query->get();

        return $this->pdfExportService->generateFromView('pdf.activities-report', [
            'activities' => $activities,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'date' => now()->format('d/m/Y'),
        ], "activities-report-{$startDate}-{$endDate}.pdf");
    }

    public function customReport(Request $request)
    {
        $view = $request->input('view');
        $data = $request->input('data', []);
        $filename = $request->input('filename', 'custom-report.pdf');

        return $this->pdfExportService->generateFromView($view, $data, $filename);
    }
}
