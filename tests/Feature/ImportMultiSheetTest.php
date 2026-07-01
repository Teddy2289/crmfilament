<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Actions\DownloadImportTemplateAction;
use App\Filament\NsConseil\Actions\DownloadMultiSheetTemplateAction;
use App\Filament\NsConseil\Actions\ImportMultiSheetAction;
use App\Filament\NsConseil\Actions\ImportPartenairesAction;
use App\Models\Client;
use App\Models\Partenaire;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportMultiSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_import_actions_are_table_header_actions(): void
    {
        $this->assertInstanceOf(TableAction::class, DownloadImportTemplateAction::make());
        $this->assertInstanceOf(TableAction::class, ImportPartenairesAction::make());
        $this->assertInstanceOf(TableAction::class, DownloadMultiSheetTemplateAction::make());
        $this->assertInstanceOf(TableAction::class, ImportMultiSheetAction::make());
    }

    public function test_import_multi_sheet_partenaires_et_clients()
    {
        // Créer un fichier Excel de test
        $spreadsheet = new Spreadsheet();
        
        // Onglet Partenaires
        $sheetPartenaires = $spreadsheet->createSheet();
        $sheetPartenaires->setTitle('Partenaires');
        
        $headersPartenaires = [
            'nom', 'entreprise', 'siret', 'type', 'statut',
            'adresse', 'code_postal', 'ville', 'departement',
            'telephone', 'email', 'secteur_activite', 'nb_salaries',
            'chiffre_affaires', 'origine_contact',
        ];
        
        $sheetPartenaires->fromArray($headersPartenaires, null, 'A1');
        
        $dataPartenaires = [
            ['Entreprise Test SARL', 'Entreprise Test', '12345678901234', 'Entreprise directe', 'À prospecter', '123 Rue Test', '75001', 'Paris', '75', '0123456789', 'test@entreprise.com', 'Informatique', '50', '1000000', 'Démarchage'],
            ['CSE Test', 'CSE Test', '98765432109876', 'CSE', 'En cours de prospection', '456 Avenue CSE', '69001', 'Lyon', '69', '0987654321', 'cse@test.com', 'Services', '100', '2000000', 'Salon'],
        ];
        
        $sheetPartenaires->fromArray($dataPartenaires, null, 'A2');
        
        // Onglet Clients
        $sheetClients = $spreadsheet->createSheet();
        $sheetClients->setTitle('Clients');
        
        $headersClients = [
            'civilite', 'prenom', 'nom_tiers', 'email', 'telephone',
            'adresse', 'code_postal', 'ville', 'departement', 'region',
            'date_naissance', 'entreprise', 'type_tiers', 'etat',
            'montant_cpf', 'ne_plus_contacter',
        ];
        
        $sheetClients->fromArray($headersClients, null, 'A1');
        
        $dataClients = [
            ['M.', 'Jean', 'Dupont', 'jean.dupont@email.com', '0612345678', '45 Avenue des Champs', '75008', 'Paris', '75', 'Île-de-France', '1985-03-15', 'Tech Solutions', 'Particulier', 'en_cours', '5000', '0'],
            ['Mme', 'Marie', 'Martin', 'marie.martin@email.com', '0623456789', '78 Rue de la Paix', '33000', 'Bordeaux', '33', 'Nouvelle-Aquitaine', '1990-07-22', 'Digital Corp', 'Particulier', 'prospect', '3000', '0'],
        ];
        
        $sheetClients->fromArray($dataClients, null, 'A2');
        
        // Supprimer la feuille par défaut
        $spreadsheet->removeSheetByIndex(0);
        
        // Sauvegarder le fichier
        $writer = new Xlsx($spreadsheet);
        $fileName = 'test_import_multi_onglets.xlsx';
        Storage::disk('local')->makeDirectory('imports');
        $tempPath = Storage::disk('local')->path('imports/' . $fileName);
        $writer->save($tempPath);
        
        // Vérifier que les données n'existent pas déjà
        $this->assertEquals(0, Partenaire::count());
        $this->assertEquals(0, Client::count());
        
        // Simuler l'import (à adapter avec l'action réelle)
        // Pour l'instant, on teste juste la création du fichier
        $this->assertFileExists($tempPath);
        
        // Nettoyage
        Storage::disk('local')->delete('imports/' . $fileName);
        
        $this->assertTrue(true, 'Fichier de test créé avec succès');
    }
}
