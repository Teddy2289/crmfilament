<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Tcpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf;
use PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf;
use Illuminate\Support\Facades\View;

class PdfExportService
{
    protected $spreadsheet;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    /**
     * Générer un PDF à partir d'une vue Blade
     */
    public function generateFromView($view, $data = [], $filename = 'rapport.pdf')
    {
        $html = View::make($view, $data)->render();

        // Utiliser DomPDF pour générer le PDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($html);
        
        return $pdf->download($filename);
    }

    /**
     * Générer un PDF à partir d'un export Excel
     */
    public function generateFromExcel($export, $filename = 'rapport.pdf')
    {
        $spreadsheet = new Spreadsheet();
        $export->collection($export->collection())->each(function ($item, $key) use ($spreadsheet, $export) {
            $row = $key + 2; // Commencer à la ligne 2 (après les headers)
            
            $mapped = $export->map($item);
            foreach ($mapped as $col => $value) {
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col + 1, $row, $value);
            }
        });

        // Ajouter les headers
        $headers = $export->headings();
        foreach ($headers as $col => $value) {
            $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow($col + 1, 1, $value);
        }

        // Générer le PDF
        $writer = new Dompdf($spreadsheet);
        $writer->save($filename);

        return response()->download($filename);
    }

    /**
     * Générer un rapport PDF personnalisé
     */
    public function generateCustomReport($data, $options = [])
    {
        $defaultOptions = [
            'title' => 'Rapport',
            'orientation' => 'portrait',
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ];

        $options = array_merge($defaultOptions, $options);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('pdf.custom-report', $data)
            ->setPaper($options['format'], $options['orientation'])
            ->setOptions([
                'margin-top' => $options['margin_top'],
                'margin-bottom' => $options['margin_bottom'],
                'margin-left' => $options['margin_left'],
                'margin-right' => $options['margin_right'],
            ]);

        return $pdf->download("{$options['title']}.pdf");
    }
}
