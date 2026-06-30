<?php

namespace App\Filament\NsConseil\Actions;

use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadImportTemplateAction extends Action
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'download_import_template');
    }
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Télécharger le modèle d\'import')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(function () {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                
                // Headers
                $headers = [
                    'nom',
                    'entreprise',
                    'siret',
                    'type',
                    'statut',
                    'adresse',
                    'code_postal',
                    'ville',
                    'departement',
                    'telephone',
                    'email',
                    'secteur_activite',
                    'nb_salaries',
                    'chiffre_affaires',
                    'origine_contact',
                ];
                
                $sheet->fromArray($headers, null, 'A1');
                
                // Example data
                $example = [
                    'Mon Entreprise SARL',
                    'Mon Entreprise',
                    '12345678901234',
                    'Entreprise directe',
                    'À prospecter',
                    '123 Rue de la République',
                    '75001',
                    'Paris',
                    '75',
                    '0123456789',
                    'contact@monentreprise.com',
                    'Informatique',
                    '50',
                    '1000000',
                    'Démarchage',
                ];
                
                $sheet->fromArray($example, null, 'A2');
                
                // Style headers
                $sheet->getStyle('A1:O1')->getFont()->setBold(true);
                $sheet->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                
                // Auto-size columns
                foreach (range('A', 'O') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
                
                $writer = new Xlsx($spreadsheet);
                $fileName = 'modele_import_partenaires.xlsx';
                $tempPath = storage_path('app/' . $fileName);
                $writer->save($tempPath);
                
                return Response::download($tempPath, $fileName)->deleteFileAfterSend(true);
            });
    }
}
