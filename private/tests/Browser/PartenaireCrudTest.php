<?php

namespace Tests\Browser;

use App\Models\Partenaire;
use App\Models\User;
use App\Enums\OrganizationType;
use App\Enums\OrganizationStatus;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PartenaireCrudTest extends DuskTestCase
{

    /**
     * Test de création d'un partenaire
     */
    public function test_can_create_partenaire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/partenaires')
                ->click('button:contains("Créer")')
                ->waitFor('input[name="data.nom"]')
                ->type('data.nom', 'Test Partenaire')
                ->type('data.entreprise', 'Test Entreprise')
                ->select('data.type', OrganizationType::CSE->value)
                ->type('data.ville', 'Paris')
                ->type('data.code_postal', '75001')
                ->type('data.telephone', '0123456789')
                ->type('data.email', 'test@example.com')
                ->press('Enregistrer')
                ->waitForLocation('/admin/partenaires')
                ->assertSee('Test Partenaire');
        });
    }

    /**
     * Test de lecture d'un partenaire
     */
    public function test_can_view_partenaire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $partenaire = Partenaire::factory()->create([
            'nom' => 'Test Partenaire View',
            'entreprise' => 'Test Entreprise',
            'ville' => 'Lyon',
        ]);

        $this->browse(function (Browser $browser) use ($user, $partenaire) {
            $browser->loginAs($user)
                ->visit('/admin/partenaires')
                ->click('a:contains("Test Partenaire View")')
                ->assertSee('Test Partenaire View')
                ->assertSee('Test Entreprise')
                ->assertSee('Lyon');
        });
    }

    /**
     * Test de modification d'un partenaire
     */
    public function test_can_edit_partenaire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $partenaire = Partenaire::factory()->create([
            'nom' => 'Test Partenaire Edit',
            'ville' => 'Marseille',
        ]);

        $this->browse(function (Browser $browser) use ($user, $partenaire) {
            $browser->loginAs($user)
                ->visit('/admin/partenaires')
                ->click('button:contains("Actions")')
                ->click('button:contains("Modifier")')
                ->waitFor('input[name="data.ville"]')
                ->clear('data.ville')
                ->type('data.ville', 'Bordeaux')
                ->press('Enregistrer')
                ->waitForLocation('/admin/partenaires')
                ->assertSee('Bordeaux');
        });
    }

    /**
     * Test de suppression d'un partenaire
     */
    public function test_can_delete_partenaire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $partenaire = Partenaire::factory()->create([
            'nom' => 'Test Partenaire Delete',
        ]);

        $this->browse(function (Browser $browser) use ($user, $partenaire) {
            $browser->loginAs($user)
                ->visit('/admin/partenaires')
                ->assertSee('Test Partenaire Delete')
                ->click('button:contains("Actions")')
                ->click('button:contains("Supprimer")')
                ->waitFor('button:contains("Confirmer")')
                ->click('button:contains("Confirmer")')
                ->waitForLocation('/admin/partenaires')
                ->assertDontSee('Test Partenaire Delete');
        });
    }

    /**
     * Test de validation du formulaire partenaire
     */
    public function test_partenaire_validation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/partenaires')
                ->click('button:contains("Créer")')
                ->waitFor('input[name="data.nom"]')
                ->press('Enregistrer')
                ->assertSee('Le champ nom est obligatoire');
        });
    }

    /**
     * Test de recherche de partenaire
     */
    public function test_can_search_partenaire(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Partenaire::factory()->create([
            'nom' => 'Partenaire Alpha',
        ]);

        Partenaire::factory()->create([
            'nom' => 'Partenaire Beta',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/partenaires')
                ->type('searchInput', 'Alpha')
                ->pause(500)
                ->assertSee('Partenaire Alpha')
                ->assertDontSee('Partenaire Beta');
        });
    }
}
