<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\ProspectResource\Pages\ListProspects;
use App\Models\Prospect;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProspectResourceFeatureTest extends TestCase
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
    public function list_prospects_shows_employee_count_column(): void
    {
        $user = $this->userWithFullAccess();

        $prospect = Prospect::factory()->create([
            'nom' => 'Entreprise test',
            'nb_salaries' => 123,
            'statut' => 'AC',
        ]);

        Livewire::actingAs($user)
            ->test(ListProspects::class)
            ->assertSuccessful()
            ->assertTableColumnExists('nb_salaries')
            ->assertTableFilterExists('nb_salaries_range')
            ->assertCanSeeTableRecords([$prospect]);
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_prospect_resource', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
