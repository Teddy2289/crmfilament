<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\CreateCrmSetting;
use App\Filament\SuperAdmin\Resources\RoleResource\Pages\EditRole;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SuperAdminInteractiveFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        AccessRightsCatalog::ensurePermissionsExist();
        Filament::setCurrentPanel(Filament::getPanel('super-admin'));
    }

    #[Test]
    public function role_edit_form_displays_grouped_interactive_permissions(): void
    {
        $user = $this->userWithFullAccess();
        $role = Role::create(['name' => 'test_role_grouped_form', 'guard_name' => 'web']);
        $role->syncPermissions([
            'prospects.view_any',
            'fields.prospects.nom.show',
        ]);

        Livewire::actingAs($user)
            ->test(EditRole::class, ['record' => $role->getRouteKey()])
            ->assertSuccessful()
            ->assertSee('Identité')
            ->assertSee('Modules')
            ->assertSee('Champs')
            ->assertSee('NS Conseil / AOPIA')
            ->assertSee('AOPIA - Prospects')
            ->assertSee('Autres tables')
            ->assertSee('Module')
            ->assertSee('Actions autorisées')
            ->assertSee('Champ')
            ->assertSee('Droits par champ');
    }

    #[Test]
    public function access_rights_catalog_adds_dynamic_model_tables(): void
    {
        $modules = AccessRightsCatalog::modules();
        $fieldModules = AccessRightsCatalog::fieldModules();

        $this->assertArrayHasKey('activite_ventes', $modules);
        $this->assertArrayHasKey('activite_ventes.view_any', $modules['activite_ventes']['permissions']);
        $this->assertArrayHasKey('activite_ventes', $fieldModules);
        $this->assertArrayHasKey('nombre_ventes_total', $fieldModules['activite_ventes']['fields']);
    }

    #[Test]
    public function crm_setting_create_form_displays_grouped_tabs(): void
    {
        $user = $this->userWithFullAccess();

        Livewire::actingAs($user)
            ->test(CreateCrmSetting::class)
            ->assertSuccessful()
            ->assertSee('Classement')
            ->assertSee('Valeur')
            ->assertSee('Documentation')
            ->assertSee('Aperçu');
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_super_admin_forms_full_access', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
