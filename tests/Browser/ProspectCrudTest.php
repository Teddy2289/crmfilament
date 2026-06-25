<?php

namespace Tests\Browser;

use App\Models\Prospect;
use App\Models\User;
use App\Models\StatutPhoning;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProspectCrudTest extends DuskTestCase
{

    /**
     * Test de création d'un prospect
     */
    public function test_can_create_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->click('button:contains("Créer")')
                ->waitFor('input[name="data.nom"]')
                ->type('data.nom', 'Test Prospect')
                ->type('data.raison_sociale', 'Test Société')
                ->type('data.telephone', '0123456789')
                ->type('data.email', 'prospect@example.com')
                ->type('data.ville', 'Paris')
                ->type('data.code_postal', '75001')
                ->select('data.statut', 'a_contacter')
                ->press('Enregistrer')
                ->waitForLocation('/admin/prospects')
                ->assertSee('Test Prospect');
        });
    }

    /**
     * Test de lecture d'un prospect
     */
    public function test_can_view_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $prospect = Prospect::factory()->create([
            'nom' => 'Test Prospect View',
            'raison_sociale' => 'Test Société View',
            'ville' => 'Lyon',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->click('a:contains("Test Prospect View")')
                ->assertSee('Test Prospect View')
                ->assertSee('Test Société View')
                ->assertSee('Lyon');
        });
    }

    /**
     * Test de modification d'un prospect
     */
    public function test_can_edit_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $prospect = Prospect::factory()->create([
            'nom' => 'Test Prospect Edit',
            'ville' => 'Marseille',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->click('button:contains("Actions")')
                ->click('button:contains("Modifier")')
                ->waitFor('input[name="data.ville"]')
                ->clear('data.ville')
                ->type('data.ville', 'Bordeaux')
                ->press('Enregistrer')
                ->waitForLocation('/admin/prospects')
                ->assertSee('Bordeaux');
        });
    }

    /**
     * Test de suppression d'un prospect
     */
    public function test_can_delete_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $prospect = Prospect::factory()->create([
            'nom' => 'Test Prospect Delete',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->assertSee('Test Prospect Delete')
                ->click('button:contains("Actions")')
                ->click('button:contains("Supprimer")')
                ->waitFor('button:contains("Confirmer")')
                ->click('button:contains("Confirmer")')
                ->waitForLocation('/admin/prospects')
                ->assertDontSee('Test Prospect Delete');
        });
    }

    /**
     * Test de validation du formulaire prospect
     */
    public function test_prospect_validation(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->click('button:contains("Créer")')
                ->waitFor('input[name="data.nom"]')
                ->press('Enregistrer')
                ->assertSee('Le champ nom est obligatoire');
        });
    }

    /**
     * Test de changement de statut prospect
     */
    public function test_can_change_prospect_statut(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $prospect = Prospect::factory()->create([
            'nom' => 'Test Prospect Statut',
            'statut' => 'a_contacter',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->click('button:contains("Actions")')
                ->click('button:contains("Modifier")')
                ->waitFor('select[name="data.statut"]')
                ->select('data.statut', 'en_cours_prospection')
                ->press('Enregistrer')
                ->waitForLocation('/admin/prospects')
                ->assertSee('en_cours_prospection');
        });
    }

    /**
     * Test de recherche de prospect
     */
    public function test_can_search_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        Prospect::factory()->create([
            'nom' => 'Prospect Alpha',
        ]);

        Prospect::factory()->create([
            'nom' => 'Prospect Beta',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/prospects')
                ->type('searchInput', 'Alpha')
                ->pause(500)
                ->assertSee('Prospect Alpha')
                ->assertDontSee('Prospect Beta');
        });
    }
}
