<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\ActiviteVenteResource\Pages\ListActiviteVentes;
use App\Models\ActiviteVente;
use App\Models\Client;
use App\Models\Consultant;
use App\Models\DossierFormation;
use App\Models\Partenaire;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ActiviteVenteAutoCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        AccessRightsCatalog::ensurePermissionsExist();
        Filament::setCurrentPanel(Filament::getPanel('ns-conseil'));
    }

    #[Test]
    public function it_recalculates_sales_from_clients_linked_to_the_partner(): void
    {
        $consultant = Consultant::create([
            'nom' => 'Durand',
            'prenom' => 'Claire',
            'statut' => 'Mandataire',
        ]);

        $partenaire = $this->createPartenaire('CSE Alpha', [
            'conseiller_id' => $consultant->id,
        ]);
        $autrePartenaire = $this->createPartenaire('CSE Hors cible');

        $clientA = $this->createClient('Client A', $partenaire);
        $clientB = $this->createClient('Client B', $partenaire);
        $clientHorsCible = $this->createClient('Client Hors cible', $autrePartenaire);

        $this->createDossier($clientA, '2025-04-12');
        $this->createDossier($clientA, '2026-01-20');
        $this->createDossier($clientB, '2026-06-15');
        $this->createDossier($clientB, null);
        $this->createDossier($clientHorsCible, '2026-07-01');

        $activite = ActiviteVente::query()
            ->where('partenaire_id', $partenaire->id)
            ->firstOrFail();

        $this->assertSame($consultant->id, $activite->consultant_id);
        $this->assertSame(3, $activite->nombre_ventes_total);
        $this->assertSame('2026-06-15', $activite->derniere_vente->toDateString());
        $this->assertSame(1, $activite->ventes_2025);
        $this->assertSame(2, $activite->ventes_2026);
        $this->assertSame(3, $partenaire->refresh()->nombre_ventes_liees);
    }

    #[Test]
    public function moving_a_client_recalculates_the_old_and_new_partner_activity(): void
    {
        $ancienPartenaire = $this->createPartenaire('CSE Ancien');
        $nouveauPartenaire = $this->createPartenaire('CSE Nouveau');
        $client = $this->createClient('Client mobile', $ancienPartenaire);

        $this->createDossier($client, '2025-02-10');
        $this->createDossier($client, '2026-03-10');

        $ancienneActivite = ActiviteVente::query()
            ->where('partenaire_id', $ancienPartenaire->id)
            ->firstOrFail();
        $this->assertSame(2, $ancienneActivite->nombre_ventes_total);

        $client->update(['partenaire_id' => $nouveauPartenaire->id]);

        $nouvelleActivite = ActiviteVente::query()
            ->where('partenaire_id', $nouveauPartenaire->id)
            ->firstOrFail();

        $this->assertSame(0, $ancienneActivite->refresh()->nombre_ventes_total);
        $this->assertSame(2, $nouvelleActivite->nombre_ventes_total);
        $this->assertSame(1, $nouvelleActivite->ventes_2025);
        $this->assertSame(1, $nouvelleActivite->ventes_2026);
    }

    #[Test]
    public function list_page_shows_linked_clients_count(): void
    {
        $user = $this->userWithFullAccess();
        $partenaire = $this->createPartenaire('CSE Liste');
        $client = $this->createClient('Client Liste', $partenaire);

        $this->createDossier($client, '2026-05-01');

        Livewire::actingAs($user)
            ->test(ListActiviteVentes::class)
            ->assertSuccessful()
            ->assertSee('CSE Liste')
            ->assertSee('1');
    }

    private function createPartenaire(string $nom, array $overrides = []): Partenaire
    {
        return Partenaire::create(array_merge([
            'nom' => $nom,
            'entreprise' => $nom,
            'ville' => 'Paris',
        ], $overrides));
    }

    private function createClient(string $nom, Partenaire $partenaire): Client
    {
        return Client::create([
            'nom_tiers' => $nom,
            'email' => str($nom)->slug()->append('@example.test')->toString(),
            'partenaire_id' => $partenaire->id,
            'ne_plus_contacter' => false,
        ]);
    }

    private function createDossier(Client $client, ?string $dateVente): DossierFormation
    {
        return DossierFormation::create([
            'personne_id' => $client->id,
            'ref_client' => $client->ref_client,
            'intitule_programme' => 'Formation '.$client->nom_tiers,
            'date_vente' => $dateVente,
        ]);
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_activite_vente', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
