<?php

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\ClientResource\Import\Crm01FcImporter;
use App\Filament\NsConseil\Resources\ClientResource\Import\CrmAopiaAboImporter;
use App\Filament\NsConseil\Resources\ClientResource\Import\CrmLikeImporter;
use App\Models\Client;
use App\Models\DossierFormation;
use App\Models\Partenaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientImportersFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function crm_like_import_links_partner_and_keeps_source_metadata_on_client(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Leroy Merlin La Rochelle',
            'entreprise' => 'Leroy Merlin',
            'ville' => 'La Rochelle',
            'type' => OrganizationType::CSE->value,
            'statut' => OrganizationStatus::AProspecter->value,
        ]);

        $result = (new CrmLikeImporter())->import([[
            'Civilité' => 'M.',
            'Réf. client' => 'LIKE CLIENT TEST EXCEL 1',
            'Tiers' => 'Client LIKE',
            'Email' => 'client-like@example.test',
            'Partenaire Like' => 'CSE Leroy Merlin La Rochelle',
            'Partenaire Boutique' => 'Boutique test',
            'Opération' => 'Operation test',
        ]]);

        $client = Client::query()->firstOrFail();

        $this->assertSame([], $result['errors']);
        $this->assertSame($partenaire->id, $client->partenaire_id);
        $this->assertSame('CSE Leroy Merlin La Rochelle', $client->extra_data['partenaire_like']);
        $this->assertSame('Boutique test', $client->extra_data['partenaire_boutique']);
        $this->assertSame('Operation test', $client->extra_data['operation']);
        $this->assertSame('rattache', $client->extra_data['partenaire_import']['statut']);
        $this->assertSame(1, DossierFormation::query()->where('ref_client', 'LIKE CLIENT TEST EXCEL 1')->count());
    }

    #[Test]
    public function crm_aopia_abo_import_keeps_interlocuteur_on_client_without_dossier_extra_fields(): void
    {
        $result = (new CrmAopiaAboImporter())->import([[
            'Réf. client' => 'TOSA PHOTOSHOP CLIENT AOPIA2',
            'Tiers' => 'Client AOPIA',
            'Email' => 'client-aopia@example.test',
            'Interlocuteur' => 'Partenaire inconnu',
            'Suivi actuel du Client' => 'Suivi manuel',
            'Montant cpf' => '500',
        ]]);

        $client = Client::query()->firstOrFail();

        $this->assertSame([], $result['errors']);
        $this->assertNull($client->partenaire_id);
        $this->assertSame('Partenaire inconnu', $client->extra_data['interlocuteur']);
        $this->assertSame('Suivi manuel', $client->extra_data['suivi_client']);
        $this->assertSame('partenaire_non_rattache', $client->extra_data['partenaire_import']['statut']);
        $this->assertSame(1, DossierFormation::query()->where('ref_client', 'TOSA PHOTOSHOP CLIENT AOPIA2')->count());
    }

    #[Test]
    public function crm_01fc_import_keeps_provenance_on_client_without_dossier_extra_fields(): void
    {
        $result = (new Crm01FcImporter())->import([[
            'Réf. client' => 'TOSA BUR EXCEL EXPERT CLIENT 01FC',
            'Tiers' => 'Client 01FC',
            'Email' => 'client-01fc@example.test',
            'Département' => '75',
            'Type du tiers' => 'Particulier',
            'Provenance' => 'Provenance manuelle',
            'Auteur' => 'Back Office',
        ]]);

        $client = Client::query()->firstOrFail();

        $this->assertSame([], $result['errors']);
        $this->assertNull($client->partenaire_id);
        $this->assertSame('Provenance manuelle', $client->extra_data['provenance']);
        $this->assertSame('Back Office', $client->extra_data['auteur']);
        $this->assertSame('partenaire_non_rattache', $client->extra_data['partenaire_import']['statut']);
        $this->assertSame(1, DossierFormation::query()->where('ref_client', 'TOSA BUR EXCEL EXPERT CLIENT 01FC')->count());
    }
}
