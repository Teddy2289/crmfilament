<?php

namespace Tests\Browser;

use App\Models\Prospect;
use App\Models\User;
use App\Models\StatutPhoning;
use App\Models\Appel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class PhoningWorkflowTest extends DuskTestCase
{
    use DatabaseMigrations;

    /**
     * Test d'accès à la page workflow phoning
     */
    public function test_can_access_phoning_workflow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->assertSee('Workflow Phoning')
                ->assertSee('File d\'attente');
        });
    }

    /**
     * Test de chargement de la file d'attente
     */
    public function test_can_load_queue(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        Prospect::factory()->count(5)->create([
            'teleprospecteur_id' => $user->id,
            'statut' => 'a_contacter',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->assertSee('5 prospects chargés');
        });
    }

    /**
     * Test d'appel d'un prospect
     */
    public function test_can_call_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        $prospect = Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Test Prospect Call',
            'telephone' => '0123456789',
            'statut' => 'a_contacter',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Appeler")')
                ->pause(500)
                ->assertSee('Appel en cours');
        });
    }

    /**
     * Test de changement de statut phoning
     */
    public function test_can_change_phoning_statut(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        $prospect = Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Test Prospect Statut',
            'statut' => 'a_contacter',
        ]);

        StatutPhoning::factory()->create([
            'code' => 'STD-Joint',
            'label' => 'Standard Joint',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Appeler")')
                ->pause(500)
                ->select('statut_phoning', 'STD-Joint')
                ->press('Enregistrer')
                ->pause(500)
                ->assertSee('STD-Joint');
        });
    }

    /**
     * Test de validation des éléments bloquants QF
     */
    public function test_qf_validation_blocks(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        $prospect = Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Test Prospect QF',
            'statut' => 'rdv_planifie',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Valider QF")')
                ->pause(500)
                ->assertSee('Éléments manquants');
        });
    }

    /**
     * Test de création de RDV depuis workflow
     */
    public function test_can_create_rdv_from_workflow(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        $prospect = Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Test Prospect RDV',
            'statut' => 'en_cours_prospection',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Appeler")')
                ->pause(500)
                ->click('button:contains("Créer RDV")')
                ->waitFor('input[name="data.date_heure"]')
                ->type('data.date_heure', '2026-06-25 10:00')
                ->press('Enregistrer')
                ->pause(500)
                ->assertSee('RDV créé');
        });
    }

    /**
     * Test d'enregistrement d'appel avec note
     */
    public function test_can_record_call_with_note(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        $prospect = Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Test Prospect Note',
            'statut' => 'a_contacter',
        ]);

        $this->browse(function (Browser $browser) use ($user, $prospect) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Appeler")')
                ->pause(500)
                ->type('note', 'Note de test pour l\'appel')
                ->select('statut_phoning', 'STD-NR')
                ->press('Enregistrer')
                ->pause(500)
                ->assertSee('Appel enregistré');
        });
    }

    /**
     * Test de passage au prospect suivant
     */
    public function test_can_go_to_next_prospect(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        Prospect::factory()->count(3)->create([
            'teleprospecteur_id' => $user->id,
            'statut' => 'a_contacter',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Suivant")')
                ->pause(500)
                ->assertSee('Prospect suivant');
        });
    }

    /**
     * Test de réinitialisation de la file d'attente
     */
    public function test_can_reset_queue(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        Prospect::factory()->count(5)->create([
            'teleprospecteur_id' => $user->id,
            'statut' => 'a_contacter',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->click('button:contains("Charger la file")')
                ->pause(1000)
                ->click('button:contains("Réinitialiser")')
                ->pause(500)
                ->assertSee('File réinitialisée');
        });
    }

    /**
     * Test de filtrage par statut phoning
     */
    public function test_can_filter_by_phoning_statut(): void
    {
        $user = User::factory()->create();
        $user->assignRole('teleprospecteur');

        Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Prospect STD-NR',
            'statut_phoning_code' => 'STD-NR',
        ]);

        Prospect::factory()->create([
            'teleprospecteur_id' => $user->id,
            'nom' => 'Prospect STD-Joint',
            'statut_phoning_code' => 'STD-Joint',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/admin/phoning-workflow')
                ->select('filter_statut', 'STD-NR')
                ->pause(500)
                ->assertSee('Prospect STD-NR')
                ->assertDontSee('Prospect STD-Joint');
        });
    }
}
