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
            'commercial_id' => null,
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

        // $retired a bien un appel (statut "supp") dans cette campagne : il apparaît
        // légitimement dans la section "Résultats des appels" (fiche par statut),
        // mais l'exclusion de la file d'attente elle-même est déjà vérifiée plus haut
        // via getContactsQueue()/countQueueContacts() — donc pas d'assertDontSee ici.
        Livewire::actingAs($user)
            ->test(ViewCampagnePhoning::class, ['record' => $campaign->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('File d&#039;attente - 1 contact(s)', false)
            ->assertSee('CSE Appelable')
            ->assertDontSee($ko->nom)
            ->assertDontSee($qf->nom);
    }

    #[Test]
    public function paused_campaigns_are_excluded_from_active_campaigns(): void
    {
        $active = CampagnePhoning::create([
            'nom' => 'Campagne active',
            'statut' => 'active',
            'type_entite' => 'prospects',
        ]);

        $paused = CampagnePhoning::create([
            'nom' => 'Campagne en pause',
            'statut' => 'en_pause',
            'type_entite' => 'prospects',
        ]);

        $this->assertTrue(CampagnePhoning::active()->whereKey($active->id)->exists());
        $this->assertFalse(CampagnePhoning::active()->whereKey($paused->id)->exists());
    }

    #[Test]
    public function paused_campaigns_are_excluded_from_the_default_phoning_queue(): void
    {
        $groupe = GroupeTelepro::create(['nom' => 'Groupe pause', 'actif' => true]);
        $telepro = User::factory()->create();
        $telepro->groupesTelepro()->attach($groupe->id);

        $active = CampagnePhoning::create([
            'nom' => 'Campagne active',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC']],
        ]);

        $paused = CampagnePhoning::create([
            'nom' => 'Campagne en pause',
            'statut' => 'en_pause',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC']],
        ]);

        $prospectActive = Prospect::factory()->create([
            'statut' => 'AC',
            'commercial_id' => null,
        ]);

        $prospectPaused = Prospect::factory()->create([
            'statut' => 'AC',
            'commercial_id' => null,
        ]);

        $queue = app(\App\Services\Phoning\PhoningQueueBuilder::class)->buildDefaultQueue($telepro->id, null);

        $this->assertNotEmpty($queue);
        $this->assertContains(['type' => 'prospect', 'id' => $prospectActive->id, 'campagne_id' => $active->id], $queue);
        $this->assertFalse(collect($queue)->contains(fn ($item) => $item['id'] === $prospectPaused->id));
    }

    #[Test]
    public function campaign_assigned_to_a_group_is_only_visible_to_its_members(): void
    {
        $groupeA = GroupeTelepro::create(['nom' => 'Groupe 44-45-75', 'actif' => true]);
        $groupeB = GroupeTelepro::create(['nom' => 'Groupe 60-80', 'actif' => true]);

        $membreGroupeA = User::factory()->create();
        $membreGroupeA->groupesTelepro()->attach($groupeA->id);

        $membreGroupeB = User::factory()->create();
        $membreGroupeB->groupesTelepro()->attach($groupeB->id);

        $sansGroupe = User::factory()->create();

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

    #[Test]
    public function a_user_belonging_to_two_groups_sees_campaigns_from_both(): void
    {
        $groupeA = GroupeTelepro::create(['nom' => 'Groupe 44', 'actif' => true]);
        $groupeB = GroupeTelepro::create(['nom' => 'Groupe 45', 'actif' => true]);

        $membreDesDeux = User::factory()->create();
        $membreDesDeux->groupesTelepro()->attach([$groupeA->id, $groupeB->id]);

        $campagneA = CampagnePhoning::create([
            'nom' => 'Campagne A',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupeA->id,
        ]);

        $campagneB = CampagnePhoning::create([
            'nom' => 'Campagne B',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupeB->id,
        ]);

        $this->assertTrue(
            CampagnePhoning::query()->forUser($membreDesDeux->id)->whereKey($campagneA->id)->exists()
        );
        $this->assertTrue(
            CampagnePhoning::query()->forUser($membreDesDeux->id)->whereKey($campagneB->id)->exists()
        );
        $this->assertCount(2, $membreDesDeux->groupesTelepro);
    }


    #[Test]
    public function phone_search_accepts_national_spaces_plus33_and_0033_formats(): void
    {
        $prospect = Prospect::factory()->create([
            'nom' => 'Recherche téléphone robuste',
            'telephone' => '05 62 57 79 90',
            'telephone_alt' => null,
            'interlocuteur_telephone' => null,
            'statut' => 'AC',
        ]);

        $service = app(\App\Services\Phoning\PhoningContactSearchService::class);
        foreach (['05 62 57 79 90', '+33 5 62 57 79 90', '0033 5 62 57 79 90', '0562577990'] as $input) {
            $results = $service->findByPhone($input);
            $this->assertTrue(
                collect($results)->contains(fn (array $result): bool => $result['type'] === 'prospect' && $result['id'] === $prospect->id),
                "Le numéro {$input} doit retrouver le prospect cible."
            );
        }
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
