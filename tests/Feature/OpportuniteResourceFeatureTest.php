<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\OpportuniteResource\Pages\ListOpportunites;
use App\Models\Opportunite;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class OpportuniteResourceFeatureTest extends TestCase
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
    public function list_opportunites_tabs_keep_the_table_query_bound_to_the_model(): void
    {
        $user = $this->userWithFullAccess();

        Opportunite::create($this->opportuniteData('Nouvelle opportunité', 'nouveau'));
        Opportunite::create($this->opportuniteData('Opportunité convertie', 'converti'));
        Opportunite::create($this->opportuniteData('Opportunité perdue', 'perdu'));

        $component = Livewire::actingAs($user)
            ->test(ListOpportunites::class)
            ->assertSuccessful()
            ->assertSet('activeTab', 'actives');

        $this->assertSame(1, $component->instance()->getFilteredTableQuery()->count());

        $component->set('activeTab', 'converties');

        $this->assertSame(1, $component->instance()->getFilteredTableQuery()->count());
    }

    private function opportuniteData(string $name, string $statut): array
    {
        return [
            'nom_entite' => $name,
            'source_detection' => 'fichier_externe',
            'potentiel' => 'moyen',
            'statut' => $statut,
            'date_detection' => now()->toDateString(),
        ];
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_opportunite_resource', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
