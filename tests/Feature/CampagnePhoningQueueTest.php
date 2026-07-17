<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages\ViewCampagnePhoning;
use App\Models\Appel;
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

class CampagnePhoningQueueTest extends TestCase
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
    public function campaign_view_displays_only_callable_contacts_in_queue(): void
    {
        $user = $this->userWithFullAccess();

        $campaign = CampagnePhoning::create([
            'nom' => 'Campagne file test',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'criteres' => [
                'statuts' => ['AC', 'KO', 'QF'],
            ],
        ]);

        $callable = Prospect::factory()->create([
            'nom' => 'CSE Appelable',
            'statut' => 'AC',
            'teleprospecteur_id' => $user->id,
        ]);

        $ko = Prospect::factory()->create([
            'nom' => 'CSE KO',
            'statut' => 'KO',
        ]);

        $qf = Prospect::factory()->create([
            'nom' => 'CSE QF',
            'statut' => 'QF',
        ]);

        $retired = Prospect::factory()->create([
            'nom' => 'CSE Retire',
            'statut' => 'AC',
        ]);

        StatutPhoning::create([
            'model_type' => 'prospect',
            'code' => 'supp',
            'label' => 'SUPP',
            'description' => 'Retiré de file',
            'ordre' => 1,
            'actif' => true,
            'retire_de_file' => true,
        ]);

        Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $retired->id,
            'user_id' => $user->id,
            'date_heure' => now(),
            'campagne_id' => $campaign->id,
            'phoning_status' => 'supp',
        ]);

        $this->assertSame(
            [['type' => 'prospect', 'id' => $callable->id, 'campagne_id' => $campaign->id]],
            $campaign->getContactsQueue()
        );
        $this->assertSame(1, $campaign->countQueueContacts());

        Livewire::actingAs($user)
            ->test(ViewCampagnePhoning::class, ['record' => $campaign->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('File d&#039;attente - 1 contact(s)', false)
            ->assertSee('CSE Appelable')
            ->assertDontSee($ko->nom)
            ->assertDontSee($qf->nom)
            ->assertDontSee($retired->nom);
    }

    #[Test]
    public function campaign_assigned_to_a_group_is_only_visible_to_its_members(): void
    {
        $groupeA = GroupeTelepro::create(['nom' => 'Groupe 44-45-75', 'actif' => true]);
        $groupeB = GroupeTelepro::create(['nom' => 'Groupe 60-80', 'actif' => true]);

        $membreGroupeA = User::factory()->create(['groupe_telepro_id' => $groupeA->id]);
        $membreGroupeB = User::factory()->create(['groupe_telepro_id' => $groupeB->id]);
        $sansGroupe = User::factory()->create(['groupe_telepro_id' => null]);

        $campagneGroupeA = CampagnePhoning::create([
            'nom' => 'Campagne 75',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupeA->id,
        ]);

        $campagneOuverte = CampagnePhoning::create([
            'nom' => 'Campagne ouverte',
            'statut' => 'active',
            'type_entite' => 'prospects',
        ]);

        $this->assertTrue(
            CampagnePhoning::query()->forUser($membreGroupeA->id)->whereKey($campagneGroupeA->id)->exists()
        );
        $this->assertFalse(
            CampagnePhoning::query()->forUser($membreGroupeB->id)->whereKey($campagneGroupeA->id)->exists()
        );
        $this->assertFalse(
            CampagnePhoning::query()->forUser($sansGroupe->id)->whereKey($campagneGroupeA->id)->exists()
        );

        // La campagne ouverte (sans user_id ni groupe) reste visible par tous.
        $this->assertTrue(
            CampagnePhoning::query()->forUser($membreGroupeB->id)->whereKey($campagneOuverte->id)->exists()
        );
        $this->assertTrue(
            CampagnePhoning::query()->forUser($sansGroupe->id)->whereKey($campagneOuverte->id)->exists()
        );
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_campaign_queue', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
