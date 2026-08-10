<?php

namespace App\Http\Controllers;

use App\Services\PdfReportService;
use Illuminate\Http\Request;

class PdfReportController extends Controller
{
    protected $pdfReportService;

    public function __construct(PdfReportService $pdfReportService)
    {
        $this->pdfReportService = $pdfReportService;
    }

    public function prospectReport($id)
    {
        $prospect = \App\Models\Prospect::findOrFail($id);
        return $this->pdfReportService->generateProspectReport($prospect);
    }

    public function clientReport($id)
    {
        $client = \App\Models\Client::findOrFail($id);
        return $this->pdfReportService->generateClientReport($client);
    }

    public function partenaireReport($id)
    {
        $partenaire = \App\Models\Partenaire::findOrFail($id);
        return $this->pdfReportService->generatePartenaireReport($partenaire);
    }

    public function dossierFormationReport($id)
    {
        $dossier = \App\Models\DossierFormation::findOrFail($id);
        return $this->pdfReportService->generateDossierFormationReport($dossier);
    }

    public function activitiesReport(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $userId = $request->input('user_id');

        return $this->pdfReportService->generateActivitiesReport($startDate, $endDate, $userId);
    }

    public function customReport(Request $request)
    {
        $view = $request->input('view');
        $data = $request->input('data', []);
        $filename = $request->input('filename', 'custom-report.pdf');

        return $this->pdfReportService->generateCustomReport($view, $data, $filename);
    }
}
