<?php

namespace Tests\Feature;

use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages\ListContactPartenaires;
use App\Filament\NsConseil\Resources\PartenaireResource\Pages\CreatePartenaire;
use App\Filament\NsConseil\Resources\PartenaireResource\Pages\EditPartenaire;
use App\Filament\NsConseil\Resources\PartenaireResource\Pages\ViewPartenaire;
use App\Filament\NsConseil\Resources\ProspectResource\Pages\CreateProspect;
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

class PartenaireCleanupVerificationTest extends TestCase
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
    public function partenaire_create_form_renders(): void
    {
        Livewire::actingAs($this->userWithFullAccess())
            ->test(CreatePartenaire::class)
            ->assertSuccessful();
    }

    #[Test]
    public function partenaire_view_and_edit_render_for_cse_type(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'CSE Test',
            'type' => OrganizationType::CSE->value,
            'ville' => 'Nantes',
            'code_postal' => '44000',
        ]);

        $user = $this->userWithFullAccess();
        $user->update(['role_cache' => User::ROLE_ADMIN]);

        Livewire::actingAs($user)
            ->test(ViewPartenaire::class, ['record' => $partenaire->getRouteKey()])
            ->assertSuccessful();

        Livewire::actingAs($user)
            ->test(EditPartenaire::class, ['record' => $partenaire->getRouteKey()])
            ->assertSuccessful();
    }

    #[Test]
    public function partenaire_create_form_can_add_a_contact_via_repeater(): void
    {
        $user = $this->userWithFullAccess();

        Livewire::actingAs($user)
            ->test(CreatePartenaire::class)
            ->fillForm([
                'nom' => 'Partenaire avec contact',
                'type' => OrganizationType::EntrepriseDirecte->value,
                'ville' => 'Lyon',
                'contacts' => [
                    ['nom' => 'Martin', 'prenom' => 'Alice', 'fonction' => 'RH'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $partenaire = Partenaire::where('nom', 'Partenaire avec contact')->firstOrFail();
        $this->assertCount(1, $partenaire->contacts);
        $this->assertSame('Martin', $partenaire->contacts->first()->nom);
    }

    #[Test]
    public function contact_partenaire_list_renders_without_role_column(): void
    {
        Livewire::actingAs($this->userWithFullAccess())
            ->test(ListContactPartenaires::class)
            ->assertSuccessful();
    }

    #[Test]
    public function prospect_create_form_renders_with_interlocuteur_prenom(): void
    {
        Livewire::actingAs($this->userWithFullAccess())
            ->test(CreateProspect::class)
            ->assertFormFieldExists('interlocuteur_prenom')
            ->assertSuccessful();
    }

    #[Test]
    public function dashboard_widget_has_view_action_pointing_to_partenaire(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Partenaire Signe',
            'type' => OrganizationType::EntrepriseDirecte->value,
            'ville' => 'Paris',
            'statut' => \App\Enums\OrganizationStatus::SigneAccordCadre->value,
            'date_modification_statut' => now(),
        ]);

        $admin = $this->userWithFullAccess();
        $admin->update(['role_cache' => 'admin']);

        Livewire::actingAs($admin)
            ->test(DirectionDerniersPartenairesWidget::class)
            ->assertSuccessful()
            ->assertTableActionExists('voir');
    }

    private function userWithFullAccess(): User
    {
        $role = Role::create(['name' => 'test_full_access_'.uniqid(), 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
