<?php

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Filament\NsConseil\Widgets\DirectionDerniersPartenairesWidget;
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

class _TmpPartenaireSlideOverDiagTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function voir_action_opens_infolist_slideover_with_partner_data(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        AccessRightsCatalog::ensurePermissionsExist();
        Filament::setCurrentPanel(Filament::getPanel('ns-conseil'));

        $role = Role::create(['name' => 'test_admin_diag', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create(['role_cache' => 'administrateur', 'actif' => true]);
        $user->assignRole($role);
        $user->assignRole(Role::firstOrCreate(['name' => 'administrateur', 'guard_name' => 'web']));

        $partenaire = Partenaire::factory()->create([
            'nom' => 'ACME DIAG TEST',
            'statut' => OrganizationStatus::SigneAccordCadre,
        ]);

        Livewire::actingAs($user)
            ->test(DirectionDerniersPartenairesWidget::class)
            ->assertSuccessful()
            ->callTableAction('voir', $partenaire)
            ->assertSuccessful()
            ->assertSee('ACME DIAG TEST');
    }
}
