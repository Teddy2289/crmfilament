<?php

namespace App\Filament\NsConseil\Actions;

use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DownloadMultiSheetTemplateAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'download_multi_sheet_template';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Télécharger le modèle multi-onglets')
            ->icon('heroicon-o-document-arrow-down')
            ->color('gray')
            ->action(function () {
                $spreadsheet = new Spreadsheet();
                
                // Partenaires sheet
                $sheetPartenaires = $spreadsheet->createSheet();
                $sheetPartenaires->setTitle('Partenaires');
                
                $headersPartenaires = [
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
                
                $sheetPartenaires->fromArray($headersPartenaires, null, 'A1');
                
                $examplePartenaire = [
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
                
                $sheetPartenaires->fromArray($examplePartenaire, null, 'A2');
                $sheetPartenaires->getStyle('A1:O1')->getFont()->setBold(true);
                $sheetPartenaires->getStyle('A1:O1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                
                foreach (range('A', 'O') as $col) {
                    $sheetPartenaires->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Clients sheet
                $sheetClients = $spreadsheet->createSheet();
                $sheetClients->setTitle('Clients');
                
                $headersClients = [
                    'civilite',
                    'prenom',
                    'nom_tiers',
                    'email',
                    'telephone',
                    'adresse',
                    'code_postal',
                    'ville',
                    'departement',
                    'region',
                    'date_naissance',
                    'entreprise',
                    'type_tiers',
                    'etat',
                    'montant_cpf',
                    'ne_plus_contacter',
                ];
                
                $sheetClients->fromArray($headersClients, null, 'A1');
                
                $exampleClient = [
                    'M.',
                    'Jean',
                    'Dupont',
                    'jean.dupont@email.com',
                    '0612345678',
                    '45 Avenue des Champs',
                    '75008',
                    'Paris',
                    '75',
                    'Île-de-France',
                    '1985-03-15',
                    'Tech Solutions',
                    'Particulier',
                    'en_cours',
                    '5000',
                    '0',
                ];
                
                $sheetClients->fromArray($exampleClient, null, 'A2');
                $sheetClients->getStyle('A1:P1')->getFont()->setBold(true);
                $sheetClients->getStyle('A1:P1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
                
                foreach (range('A', 'P') as $col) {
                    $sheetClients->getColumnDimension($col)->setAutoSize(true);
                }
                
                // Remove default sheet
                $spreadsheet->removeSheetByIndex(0);
                
                $writer = new Xlsx($spreadsheet);
                $fileName = 'modele_import_multi_onglets.xlsx';
                $tempPath = storage_path('app/' . $fileName);
                $writer->save($tempPath);
                
                return Response::download($tempPath, $fileName)->deleteFileAfterSend(true);
            });
    }
}
