<?php

namespace Tests\Feature;

use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\ProspectResource\Pages\ListProspects;
use App\Models\Prospect;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use App\Support\PhoneNumber;
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
        $role = Role::create([
            'name' => 'test_full_access_prospect_resource',
            'guard_name' => 'web',
        ]);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}

// Tests Pest complémentaires conservés dans le fichier de régression ProspectResource.
uses(\Tests\TestCase::class, RefreshDatabase::class);

test('normalise et formate les variantes françaises d’un même numéro', function (string $input, string $normalized, string $formatted) {
    expect(PhoneNumber::normalize($input))->toBe($normalized)
        ->and(PhoneNumber::format($input))->toBe($formatted);
})->with([
    ['01 00 00 00 05', '+33100000005', '01 00 00 00 05'],
    ['01-00-00-00-05', '+33100000005', '01 00 00 00 05'],
    ['0100000005', '+33100000005', '01 00 00 00 05'],
    ['+33 1 00 00 00 05', '+33100000005', '01 00 00 00 05'],
    ['+33100000005', '+33100000005', '01 00 00 00 05'],
]);

test('retrouve un prospect avec espaces tirets et préfixe +33', function (string $search) {
    $match = Prospect::factory()->create([
        'nom' => 'Prospect téléphone test',
        'telephone' => '01 00 00 00 05',
        'statut' => 'AC',
    ]);
    $other = Prospect::factory()->create([
        'nom' => 'Prospect téléphone différent',
        'telephone' => '02 00 00 00 06',
        'statut' => 'AC',
    ]);

    $query = PhoneNumber::applySearch(Prospect::query(), 'telephone', $search);

    expect($query->pluck('id')->all())
        ->toContain($match->id)
        ->not->toContain($other->id);
})->with([
    'espaces' => '01 00 00 00 05',
    'tirets' => '01-00-00-00-05',
    'chiffres seuls' => '0100000005',
    'international' => '+33 1 00 00 00 05',
]);

test('expose le filtre de zone téléphonique dans la table ProspectResource', function () {
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    AccessRightsCatalog::ensurePermissionsExist();
    Filament::setCurrentPanel(Filament::getPanel('ns-conseil'));

    $role = Role::create([
        'name' => 'test_phone_zone_filter',
        'guard_name' => 'web',
    ]);
    AccessRightsCatalog::syncFullAccess($role);
    $user = User::factory()->create([
        'role_cache' => $role->name,
        'actif' => true,
    ]);
    $user->assignRole($role);

    Livewire::actingAs($user)
        ->test(ListProspects::class)
        ->assertSuccessful()
        ->assertTableFilterExists('telephone_zone');
});
