<?php

namespace Tests\Feature;

use App\Enums\ProspectStatut;
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

class ProspectDepartmentFilterTest extends TestCase
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
    public function prospect_list_can_filter_by_department(): void
    {
        $user = $this->userWithFullAccess();

        $lille = Prospect::create([
            'nom' => 'Prospect Lille',
            'departement' => '59',
            'statut' => ProspectStatut::AC->value,
        ]);

        Prospect::create([
            'nom' => 'Prospect Paris',
            'departement' => '75',
            'statut' => ProspectStatut::AC->value,
        ]);

        $component = Livewire::actingAs($user)
            ->test(ListProspects::class)
            ->assertSuccessful()
            ->assertTableFilterExists('departement')
            ->assertCountTableRecords(2);

        $component->filterTable('departement', '59');

        $this->assertSame(
            [$lille->id],
            $component->instance()->getFilteredTableQuery()->pluck('id')->all()
        );
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_prospect_filter_'.uniqid(), 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
