<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CrmDashboardDataService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CrmDashboardController extends Controller
{
    public function getData(Request $request, CrmDashboardDataService $service): JsonResponse
    {
        return response()->json($this->data($request, $service), 200, ['Cache-Control' => 'no-store, no-cache, must-revalidate'], JSON_UNESCAPED_UNICODE);
    }

    public function exportExcel(Request $request, CrmDashboardDataService $service)
    {
        $data = $this->data($request, $service);
        $filename = 'crm-dashboard-'.now()->format('Ymd-His').'.xlsx';

        return response()->streamDownload(function () use ($data): void {
            $book = new Spreadsheet();
            $sheet = $book->getActiveSheet();
            $sheet->setTitle('Dashboard CRM');
            $sheet->fromArray([['Dashboard CRM', $data['period']['label'] ?? 'Toutes les périodes']], null, 'A1');
            $sheet->fromArray([['KPI', 'Valeur', 'Période précédente', 'Évolution %']], null, 'A3');
            $row = 4;
            foreach ($data['kpis'] ?? [] as $kpi) {
                $comparison = $kpi['comparison'] ?? null;
                $sheet->fromArray([[$kpi['label'] ?? $kpi['key'], (int) ($kpi['value'] ?? 0), $comparison['previous'] ?? '', $comparison['percent'] ?? '']], null, 'A'.$row++);
            }
            $row += 1;
            $sheet->fromArray([['Pipeline actuel', 'Total']], null, 'A'.$row++);
            foreach ($data['pipeline'] ?? [] as $item) {
                $sheet->fromArray([[$item['label'] ?? '', (int) ($item['total'] ?? 0)]], null, 'A'.$row++);
            }
            $row += 1;
            $sheet->fromArray([['Évolution temporelle', 'Statut', 'Total']], null, 'A'.$row++);
            foreach ($data['pipeline_trend'] ?? [] as $item) {
                $sheet->fromArray([[$item['bucket'] ?? '', $item['label'] ?? '', (int) ($item['total'] ?? 0)]], null, 'A'.$row++);
            }
            foreach (['A1:D1', 'A3:D3'] as $range) {
                $sheet->getStyle($range)->getFont()->setBold(true);
            }
            foreach (range('A', 'D') as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }
            (new Xlsx($book))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exportPdf(Request $request, CrmDashboardDataService $service)
    {
        $data = $this->data($request, $service);
        $html = view('filament.ns-conseil.exports.crm-dashboard', compact('data'))->render();
        return Pdf::loadHTML($html)->setPaper('a4', 'landscape')->download('crm-dashboard-'.now()->format('Ymd-His').'.pdf');
    }

    private function data(Request $request, CrmDashboardDataService $service): array
    {
        return $service->getData(
            $request->string('period')->toString(),
            $request->string('start_date')->toString(),
            $request->string('end_date')->toString(),
        );
    }
}

