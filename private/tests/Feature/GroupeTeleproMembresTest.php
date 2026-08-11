<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages\CreateGroupeTelepro;
use App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages\EditGroupeTelepro;
use App\Models\GroupeTelepro;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class GroupeTeleproMembresTest extends TestCase
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
    public function creating_a_group_persists_all_selected_members(): void
    {
        $admin = $this->userWithFullAccess();
        $telepro1 = User::factory()->create(['role_cache' => 'teleprospecteur']);
        $telepro2 = User::factory()->create(['role_cache' => 'teleprospecteur']);

        Livewire::actingAs($admin)
            ->test(CreateGroupeTelepro::class)
            ->fillForm([
                'nom' => 'Groupe test création',
                'actif' => true,
                'membres' => [$telepro1->id, $telepro2->id],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $groupe = GroupeTelepro::where('nom', 'Groupe test création')->firstOrFail();

        $this->assertCount(2, $groupe->membres()->get());
        $this->assertTrue($telepro1->fresh()->groupesTelepro->contains('id', $groupe->id));
        $this->assertTrue($telepro2->fresh()->groupesTelepro->contains('id', $groupe->id));
    }

    #[Test]
    public function editing_a_group_to_add_a_second_member_keeps_both(): void
    {
        $admin = $this->userWithFullAccess();
        $telepro1 = User::factory()->create(['role_cache' => 'teleprospecteur']);
        $telepro2 = User::factory()->create(['role_cache' => 'teleprospecteur']);

        $groupe = GroupeTelepro::create(['nom' => 'Groupe édité', 'actif' => true]);
        $groupe->membres()->attach($telepro1->id);

        Livewire::actingAs($admin)
            ->test(EditGroupeTelepro::class, ['record' => $groupe->getRouteKey()])
            ->fillForm([
                'membres' => [$telepro1->id, $telepro2->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(2, $groupe->membres()->get());
    }

    #[Test]
    public function a_teleprospecteur_can_belong_to_two_groups_simultaneously(): void
    {
        $admin = $this->userWithFullAccess();
        $telepro = User::factory()->create(['role_cache' => 'teleprospecteur']);

        $groupeA = GroupeTelepro::create(['nom' => 'Groupe A', 'actif' => true]);
        $groupeA->membres()->attach($telepro->id);

        $groupeB = GroupeTelepro::create(['nom' => 'Groupe B', 'actif' => true]);

        Livewire::actingAs($admin)
            ->test(EditGroupeTelepro::class, ['record' => $groupeB->getRouteKey()])
            ->fillForm([
                'membres' => [$telepro->id],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertCount(2, $telepro->fresh()->groupesTelepro);
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_groupe_telepro', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
