<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Models\CampagnePhoning;
use App\Models\GroupeTelepro;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PhoningWorkflowContactsRestantsTest extends TestCase
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
    public function it_sums_the_queue_counts_of_all_the_users_campaigns_when_no_filter_is_selected(): void
    {
        $groupe = GroupeTelepro::create(['nom' => 'Groupe 44-45', 'actif' => true]);
        $user = $this->userWithFullAccess();
        $user->groupesTelepro()->attach($groupe->id);

        CampagnePhoning::create([
            'nom' => 'Campagne A', 'statut' => 'active', 'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC'], 'departement' => '44'],
        ]);
        CampagnePhoning::create([
            'nom' => 'Campagne B', 'statut' => 'active', 'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC'], 'departement' => '45'],
        ]);

        Prospect::factory()->count(3)->create(['statut' => 'AC', 'departement' => '44', 'commercial_id' => null]);
        Prospect::factory()->count(2)->create(['statut' => 'AC', 'departement' => '45', 'commercial_id' => null]);

        $component = Livewire::actingAs($user)->test(PhoningWorkflow::class);

        $this->assertSame(5, $component->instance()->getContactsRestantsCount());
    }

    #[Test]
    public function selecting_a_single_campaign_restricts_the_count_to_it(): void
    {
        $groupe = GroupeTelepro::create(['nom' => 'Groupe 44-45', 'actif' => true]);
        $user = $this->userWithFullAccess();
        $user->groupesTelepro()->attach($groupe->id);

        $campagneA = CampagnePhoning::create([
            'nom' => 'Campagne A', 'statut' => 'active', 'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC'], 'departement' => '44'],
        ]);
        CampagnePhoning::create([
            'nom' => 'Campagne B', 'statut' => 'active', 'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC'], 'departement' => '45'],
        ]);

        Prospect::factory()->count(3)->create(['statut' => 'AC', 'departement' => '44', 'commercial_id' => null]);
        Prospect::factory()->count(2)->create(['statut' => 'AC', 'departement' => '45', 'commercial_id' => null]);

        $component = Livewire::actingAs($user)
            ->test(PhoningWorkflow::class)
            ->call('selectCampagne', $campagneA->id);

        $this->assertSame(3, $component->instance()->getContactsRestantsCount());
    }

    #[Test]
    public function a_no_answer_result_does_not_lower_the_remaining_count_but_advancing_the_working_queue_does(): void
    {
        $user = $this->userWithFullAccess();

        $campagne = CampagnePhoning::create([
            'nom' => 'Campagne unique', 'statut' => 'active', 'type_entite' => 'prospects',
            'criteres' => ['statuts' => ['AC']],
        ]);

        StatutPhoning::create([
            'model_type' => 'prospect', 'code' => 'nrp', 'label' => 'NRP',
            'description' => 'Non répondu', 'ordre' => 1, 'actif' => true,
            'retire_de_file' => false, 'compte_comme_tentative' => true,
        ]);

        Prospect::factory()->count(2)->create(['statut' => 'AC', 'commercial_id' => null]);

        $component = Livewire::actingAs($user)->test(PhoningWorkflow::class);

        $this->assertSame(2, $component->instance()->getContactsRestantsCount());
        $this->assertCount(2, $component->instance()->contactQueue);

        $component->set('statut_resultat', 'nrp')->call('submitResult');

        // Le prospect reste "AC" (NRP ne change pas son statut) : il doit
        // toujours être compté comme à rappeler, même s'il vient de sortir
        // de la file de travail de cette session.
        $this->assertSame(2, $component->instance()->getContactsRestantsCount());
        $this->assertCount(1, $component->instance()->contactQueue);
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_phoning_workflow', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
