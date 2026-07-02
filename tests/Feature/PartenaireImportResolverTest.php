<?php

namespace Tests\Feature;

use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\PartenaireResource\Import\PartenaireImportResolver;
use App\Models\Consultant;
use App\Models\Partenaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PartenaireImportResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_partner_workbook_with_standard_and_sales_sheets(): void
    {
        Consultant::create([
            'nom' => 'JACQUART',
            'prenom' => 'Marie-Hélène',
            'statut' => 'Mandataire',
        ]);

        $path = $this->makePartnerWorkbook();

        $this->assertSame([
            'MAJ',
            'PERMANENCES ET VENTES 2025&2026',
        ], array_values(PartenaireImportResolver::listImportableSheets($path)));

        $result = PartenaireImportResolver::importFile($path);

        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([
            'MAJ',
            'PERMANENCES ET VENTES 2025&2026',
        ], $result['sheets_processed']);

        $alpha = Partenaire::where('nom', 'ALPHA CSE')->firstOrFail();
        $this->assertSame('PARIS', $alpha->ville);
        $this->assertSame('CSE', $alpha->type->value);
        $this->assertSame(4, $alpha->activiteVente->ventes_2025);
        $this->assertSame(3, $alpha->activitePermanence->nbre_2026);

        $beta = Partenaire::where('nom', 'BETA CLUB')->firstOrFail();
        $this->assertSame(OrganizationType::Association, $beta->type);
        $this->assertSame('LYON', $beta->ville);
        $this->assertSame('Marie-Hélène', $beta->conseiller->prenom);
        $this->assertSame(8, $beta->activiteVente->ventes_2025);
        $this->assertSame(3, $beta->activitePermanence->prc_2026);
    }

    private function makePartnerWorkbook(): string
    {
        Storage::disk('local')->makeDirectory('tests/imports');
        $path = Storage::disk('local')->path('tests/imports/partenaires_multi_onglets.xlsx');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->setTitle('MAJ');
        $spreadsheet->getActiveSheet()->fromArray($this->standardHeaders(), null, 'A1');
        $spreadsheet->getActiveSheet()->fromArray($this->standardRow(), null, 'A2');

        $salesSheet = $spreadsheet->createSheet();
        $salesSheet->setTitle('PERMANENCES ET VENTES 2025&2026');
        $salesSheet->fromArray($this->salesHeaders(), null, 'A1');
        $salesSheet->fromArray($this->salesRow(), null, 'A2');

        $ignoredSheet = $spreadsheet->createSheet();
        $ignoredSheet->setTitle('Notes');
        $ignoredSheet->fromArray(['Commentaire'], null, 'A1');
        $ignoredSheet->fromArray(['Ne pas importer'], null, 'A2');

        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function standardHeaders(): array
    {
        return [
            'Entité',
            'ENTREPRISE',
            'NOM RETENU (NOM + VILLE + DEPT + TYPE)',
            'Nb salariés',
            'Statut',
            'Année',
            'Date de signature',
            "Nombre\nde ventes",
            "Dernière\nvente",
            "Ventes\n2025",
            "Ventes\n2026",
            "Dernière\npermanence",
            "Nbre permanence\n2025",
            "Nbre permanence\n2026",
            'TYPE',
            'Origine du partenariats',
            'PARRAIN/MARRAINE',
            'Conseiller',
            'Ancien conseiller',
            'Mandataire/VDI',
            'Département conseiller',
            'Adresse CSE',
            'Code postal CSE',
            'Commune CSE',
            'Nom du contact',
            'Prénom du contact',
            'Fonction du contact',
            'Mail',
            'Tél portable',
            'Tél fixe',
            'Préférence de contact',
            'Autres interlocuteurs',
            "Parrainage d'entreprise ?",
            'Possibilité de permanence ?',
            'Réplicable',
            'Prix du PC',
            'Aopia',
            'Tarifs',
            'Part CSE',
            'Part salarié',
            'Tarifs à afficher sur la comm',
            'Adresse de factu si participation du CSE',
            'COMMENTAIRES',
        ];
    }

    private function standardRow(): array
    {
        $row = array_fill(0, 43, null);
        $row[0] = 'AOPIA';
        $row[1] = 'ALPHA CSE';
        $row[2] = 'ALPHA CSE PARIS 75 CSE';
        $row[3] = 120;
        $row[4] = 'Signée';
        $row[5] = 2025;
        $row[6] = 45687;
        $row[7] = 10;
        $row[8] = 46076;
        $row[9] = 4;
        $row[10] = 6;
        $row[11] = 46008;
        $row[12] = 2;
        $row[13] = 3;
        $row[14] = 'CSE';
        $row[17] = 'JACQUART Marie-Hélène';
        $row[20] = '75';
        $row[21] = '1 rue Exemple';
        $row[22] = '75001';
        $row[23] = 'PARIS';
        $row[24] = 'DUPONT';
        $row[25] = 'Anne';
        $row[26] = 'Secrétaire';
        $row[27] = 'anne@example.test';
        $row[28] = '06 12 34 56 78';
        $row[33] = 'OUI';
        $row[35] = 95;
        $row[36] = 20;
        $row[37] = 75;
        $row[38] = 0;
        $row[39] = 75;
        $row[40] = 75;

        return $row;
    }

    private function salesHeaders(): array
    {
        $headers = array_fill(0, 42, null);
        $headers[1] = 'ENTREPRISE';
        $headers[2] = 'NOM RETENU (NOM + VILLE + DEPT + TYPE)';
        $headers[3] = 'Nb salariés';
        $headers[4] = 'Statut';
        $headers[5] = 'Année';
        $headers[6] = 'Date signature';
        $headers[7] = 'TYPE';
        $headers[8] = 'Origine du partenariats';
        $headers[9] = 'PARRAIN/MARRAINE';
        $headers[10] = 'Nom du conseiller';
        $headers[11] = 'Prénom du conseiller';
        $headers[12] = 'Mandataire/VDI';
        $headers[13] = 'Département conseiller';
        $headers[14] = 'Adresse CSE';
        $headers[15] = 'Code postal CSE';
        $headers[16] = 'Commune CSE';
        $headers[17] = 'Nom du contact';
        $headers[18] = 'Prénom du contact';
        $headers[19] = 'Fonction du contact';
        $headers[20] = 'Mail';
        $headers[21] = 'Tél portable';
        $headers[22] = 'Tél fixe';
        $headers[23] = 'Préférence de contact';
        $headers[24] = 'Autres interlocuteurs';
        $headers[25] = "Parrainage d'entreprise ?";
        $headers[26] = 'Possibilité de permanence ?';
        $headers[27] = 'Réplicable';
        $headers[28] = 'Prix du PC';
        $headers[29] = 'Aopia';
        $headers[30] = 'Tarifs';
        $headers[31] = 'Part CSE';
        $headers[32] = 'Part salarié';
        $headers[33] = 'Tarifs à afficher sur la comm';
        $headers[34] = 'Adresse de factu si participation du CSE';
        $headers[35] = 'COMMENTAIRES';
        $headers[36] = 'Dernière vente';
        $headers[37] = "Ventes\n2025";
        $headers[38] = "Ventes\n2026";
        $headers[39] = 'Dernière permanence';
        $headers[40] = "Nbre permanence\n2025";
        $headers[41] = 'PRC 2026';

        return $headers;
    }

    private function salesRow(): array
    {
        $row = array_fill(0, 42, null);
        $row[0] = 'AOPIA';
        $row[1] = 'BETA CLUB';
        $row[2] = 'BETA CLUB LYON 69 ASS';
        $row[3] = 35;
        $row[4] = 'Signée';
        $row[5] = 2022;
        $row[6] = 44641;
        $row[7] = 'Associations';
        $row[10] = 'JACQUART';
        $row[11] = 'Marie-Hélène';
        $row[13] = '69';
        $row[14] = '2 rue Exemple';
        $row[15] = '69001';
        $row[16] = 'LYON';
        $row[17] = 'MARTIN';
        $row[18] = 'Paul';
        $row[19] = 'Président';
        $row[20] = 'paul@example.test';
        $row[28] = 95;
        $row[29] = 0;
        $row[30] = '95 €';
        $row[31] = '20 €';
        $row[36] = 45805;
        $row[37] = '8 à 10';
        $row[38] = 1;
        $row[39] = 45797;
        $row[40] = 2;
        $row[41] = 3;

        return $row;
    }
}
