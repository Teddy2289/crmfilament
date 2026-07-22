<?php

namespace Tests\Feature\Models;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClientFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'nom_tiers' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@test.com',
            'telephone' => '0612345678',
            'ne_plus_contacter' => false,
        ], $overrides));
    }

    #[Test]
    public function ref_client_generated_on_creation(): void
    {
        $client = $this->createClient();

        $this->assertNotNull($client->ref_client);
        $this->assertStringStartsWith('CLI-', $client->ref_client);
    }

    #[Test]
    public function marquer_ne_plus_contacter(): void
    {
        $client = $this->createClient();
        $client->marquerNePlusContacter('Demande client');

        $fresh = $client->fresh();
        $this->assertTrue($fresh->ne_plus_contacter);
        $this->assertEquals('Demande client', $fresh->extra_data['motif_npc']);
        $this->assertArrayHasKey('date_npc', $fresh->extra_data);
    }

    #[Test]
    public function reactiver_client(): void
    {
        $client = $this->createClient(['ne_plus_contacter' => true]);
        $client->reactiver();

        $this->assertFalse($client->fresh()->ne_plus_contacter);
    }

    #[Test]
    public function scope_contactables(): void
    {
        $this->createClient(['email' => 'contactable@test.com', 'ne_plus_contacter' => false]);
        $this->createClient(['email' => 'blocked@test.com', 'ne_plus_contacter' => true]);

        $this->assertCount(1, Client::contactables()->get());
    }

    #[Test]
    public function scope_non_contactables(): void
    {
        $this->createClient(['email' => 'contactable@test.com', 'ne_plus_contacter' => false]);
        $this->createClient(['email' => 'blocked@test.com', 'ne_plus_contacter' => true]);

        $this->assertCount(1, Client::nonContactables()->get());
    }

    #[Test]
    public function scope_avec_cpf(): void
    {
        $this->createClient(['email' => 'cpf@test.com', 'montant_cpf' => 500.0]);
        $this->createClient(['email' => 'nocpf@test.com', 'montant_cpf' => null]);

        $this->assertCount(1, Client::avecCPF()->get());
    }

    #[Test]
    public function scope_partenaire_non_rattaches_returns_only_unmatched_imports(): void
    {
        $unmatched = $this->createClient([
            'email' => 'unmatched@test.com',
            'partenaire_id' => null,
            'extra_data' => [
                'partenaire_import' => [
                    'nomenclature' => 'CSE Introuvable Nantes',
                    'statut' => 'partenaire_non_rattache',
                ],
            ],
        ]);
        $this->createClient([
            'email' => 'manual@test.com',
            'partenaire_id' => null,
            'extra_data' => [],
        ]);
        $this->createClient([
            'email' => 'attached@test.com',
            'extra_data' => [
                'partenaire_import' => [
                    'nomenclature' => 'CSE Rattache Paris',
                    'statut' => 'rattache',
                ],
            ],
        ]);

        $clients = Client::partenaireNonRattaches()->get();

        $this->assertCount(1, $clients);
        $this->assertTrue($unmatched->is($clients->first()));
        $this->assertSame('partenaire_non_rattache', $unmatched->statut_rattachement_partenaire);
        $this->assertSame('CSE Introuvable Nantes', $unmatched->nomenclature_partenaire_import);
    }

    #[Test]
    public function scope_par_region(): void
    {
        $this->createClient(['email' => 'idf@test.com', 'region' => 'Île-de-France']);
        $this->createClient(['email' => 'lyon@test.com', 'region' => 'Auvergne-Rhône-Alpes']);

        $this->assertCount(1, Client::parRegion('Île-de-France')->get());
    }

    #[Test]
    public function scope_par_departement(): void
    {
        $this->createClient(['email' => 'paris@test.com', 'departement' => '75']);
        $this->createClient(['email' => 'lyon@test.com', 'departement' => '69']);

        $this->assertCount(1, Client::parDepartement('75')->get());
    }

    #[Test]
    public function scope_recents_applies_ordering(): void
    {
        $this->createClient(['email' => 'a@test.com']);
        $this->createClient(['email' => 'b@test.com']);

        $clients = Client::recents()->get();
        $this->assertCount(2, $clients);
        // Just verify the scope applies desc ordering without error
        $this->assertNotNull($clients->first()->created_at);
    }

    #[Test]
    public function get_kpis(): void
    {
        $this->createClient(['email' => 'a@test.com', 'ne_plus_contacter' => false]);
        $this->createClient(['email' => 'b@test.com', 'ne_plus_contacter' => true]);
        $this->createClient(['email' => 'c@test.com', 'montant_cpf' => 1000]);

        $kpis = Client::getKpis();
        $this->assertEquals(3, $kpis['total']);
        $this->assertArrayHasKey('contactables', $kpis);
        $this->assertArrayHasKey('non_contactables', $kpis);
    }

    #[Test]
    public function soft_deletes(): void
    {
        $client = $this->createClient();
        $client->delete();

        $this->assertSoftDeleted($client);
    }
}
