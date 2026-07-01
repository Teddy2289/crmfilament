<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\ClientResource\Pages\ListClients;
use App\Models\Client;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ClientResourceFeatureTest extends TestCase
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
    public function list_clients_can_filter_unmatched_partner_imports(): void
    {
        $user = $this->userWithFullAccess();

        $unmatched = $this->createClient([
            'nom_tiers' => 'Client sans rattachement',
            'email' => 'sans-rattachement@example.test',
            'partenaire_id' => null,
            'extra_data' => [
                'partenaire_import' => [
                    'nomenclature' => 'CSE Introuvable Nantes',
                    'statut' => 'partenaire_non_rattache',
                ],
            ],
        ]);

        $manual = $this->createClient([
            'nom_tiers' => 'Client manuel',
            'email' => 'manuel@example.test',
            'partenaire_id' => null,
            'extra_data' => [],
        ]);

        $linked = $this->createClient([
            'nom_tiers' => 'Client rattache',
            'email' => 'rattache@example.test',
            'extra_data' => [
                'partenaire_import' => [
                    'nomenclature' => 'CSE Rattache Paris',
                    'statut' => 'rattache',
                ],
            ],
        ]);

        $component = Livewire::actingAs($user)
            ->test(ListClients::class)
            ->assertSuccessful()
            ->assertTableFilterExists('partenaire_non_rattache')
            ->assertCountTableRecords(3);

        $component
            ->filterTable('partenaire_non_rattache')
            ->assertSet('tableFilters.partenaire_non_rattache.isActive', true);

        $this->assertSame(
            [$unmatched->id],
            $component->instance()->getFilteredTableQuery()->pluck('id')->all()
        );

        $component->resetTableFilters();

        $this->assertSame(3, $component->instance()->getFilteredTableQuery()->count());
    }

    private function createClient(array $overrides = []): Client
    {
        return Client::create(array_merge([
            'nom_tiers' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean@example.test',
            'telephone' => '0612345678',
            'ne_plus_contacter' => false,
        ], $overrides));
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_client_resource', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
