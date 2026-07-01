<?php

namespace Tests\Browser;

use App\Models\CrmProfile;
use App\Models\User;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\DuskTestCase;

class PhoningWorkflowTest extends DuskTestCase
{
    public function test_can_access_phoning_workflow(): void
    {
        $user = $this->administrateurNsConseil();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/ns-conseil/phoning-workflow')
                ->assertSee('Flux de travail téléphonique');
        });
    }

    public function test_empty_queue_state_exposes_current_actions(): void
    {
        $user = $this->administrateurNsConseil();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/ns-conseil/phoning-workflow')
                ->assertSee('File vide')
                ->assertSee('Choisir une campagne')
                ->assertSee('Rafraîchir')
                ->assertSee('Gérer la file');
        });
    }

    public function test_can_access_phoning_back_office(): void
    {
        $user = $this->administrateurNsConseil();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/ns-conseil/phoning-back-office')
                ->assertSee('File d\'appels — Back-office');
        });
    }

    private function administrateurNsConseil(): User
    {
        Role::firstOrCreate(['name' => 'administrateur', 'guard_name' => 'web']);

        CrmProfile::updateOrCreate(
            ['role_name' => 'administrateur'],
            [
                'label' => 'Administrateur',
                'description' => 'Profil de test Dusk',
                'panels' => ['ns-conseil'],
                'landing_path' => '/ns-conseil',
                'ordre' => 1,
                'can_validate_qf' => true,
                'can_import' => true,
                'is_supervisor' => true,
                'is_system' => true,
                'actif' => true,
            ],
        );

        $user = User::factory()->create([
            'role_cache' => 'administrateur',
            'actif' => true,
        ]);

        $user->assignRole('administrateur');

        return $user;
    }
}
