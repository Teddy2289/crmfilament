<?php

namespace Tests\Feature;

use App\Filament\Allopro\Resources\TicketResource;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Filament\NsConseil\Resources\ClientResource;
use App\Filament\NsConseil\Resources\DocumentKnowledgeResource;
use App\Filament\NsConseil\Resources\DossierFormationResource;
use App\Filament\NsConseil\Resources\EntrepriseResource;
use App\Filament\NsConseil\Resources\OpportuniteResource;
use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Filament\NsConseil\Resources\ScriptAppelResource;
use App\Filament\NsConseil\Resources\StatutPhoningResource;
use App\Models\DocumentKnowledge;
use App\Models\Opportunite;
use App\Models\User;
use App\Support\AccessRightsCatalog;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RoleAccessRightsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        AccessRightsCatalog::ensurePermissionsExist();
    }

    #[Test]
    public function all_access_role_can_use_main_ns_conseil_and_allopro_resources(): void
    {
        $role = Role::create(['name' => 'test_full_access', 'guard_name' => 'web']);
        AccessRightsCatalog::syncFullAccess($role);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $this->assertTrue(ProspectResource::canAccess());
        $this->assertTrue(PartenaireResource::canAccess());
        $this->assertTrue(ClientResource::canAccess());
        $this->assertTrue(OpportuniteResource::canAccess());
        $this->assertTrue(RendezVousResource::canAccess());
        $this->assertTrue(EntrepriseResource::canAccess());
        $this->assertTrue(CampagnePhoningResource::canAccess());
        $this->assertTrue(DossierFormationResource::canAccess());
        $this->assertTrue(ScriptAppelResource::canAccess());
        $this->assertTrue(StatutPhoningResource::canAccess());
        $this->assertTrue(DocumentKnowledgeResource::canAccess());
        $this->assertTrue(DocumentKnowledgeResource::canCreate());
        $this->assertTrue(TicketResource::canAccess());
        $this->assertTrue(TicketResource::canCreate());
        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'prospects', 'nom', 'flux'));
        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'opportunites', 'nom_entite', 'edit'));
    }

    #[Test]
    public function selective_ns_conseil_role_only_accesses_selected_entities(): void
    {
        $role = Role::create(['name' => 'test_selective_ns', 'guard_name' => 'web']);
        $role->syncPermissions([
            'prospects.view_any',
            'prospects.view',
            'prospects.create',
            'prospects.update',
        ]);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $this->assertTrue(ProspectResource::canAccess());
        $this->assertTrue(ProspectResource::canCreate());
        $this->assertFalse(PartenaireResource::canAccess());
        $this->assertFalse(ClientResource::canAccess());
        $this->assertFalse(OpportuniteResource::canAccess());
        $this->assertFalse(RendezVousResource::canAccess());
        $this->assertFalse(EntrepriseResource::canAccess());
        $this->assertFalse(CampagnePhoningResource::canAccess());
        $this->assertFalse(DossierFormationResource::canAccess());
        $this->assertFalse(ScriptAppelResource::canAccess());
        $this->assertFalse(StatutPhoningResource::canAccess());
        $this->assertFalse(TicketResource::canAccess());
    }

    #[Test]
    public function selective_ns_conseil_role_accesses_new_crm_modules_only_when_granted(): void
    {
        $role = Role::create(['name' => 'test_selective_new_crm_modules', 'guard_name' => 'web']);
        $role->syncPermissions([
            'opportunites.view_any',
            'opportunites.view',
            'opportunites.update',
            'rendez_vous.view_any',
            'rendez_vous.view',
            'entreprises.view_any',
            'campagne_phonings.view_any',
            'script_appels.view_any',
            'statut_phonings.view_any',
            'dossier_formations.view_any',
        ]);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $opportunite = Opportunite::make(['statut' => 'nouveau']);

        $this->assertTrue(OpportuniteResource::canAccess());
        $this->assertTrue(OpportuniteResource::canView($opportunite));
        $this->assertTrue(OpportuniteResource::canEdit($opportunite));
        $this->assertFalse(OpportuniteResource::canCreate());
        $this->assertTrue(RendezVousResource::canAccess());
        $this->assertTrue(EntrepriseResource::canAccess());
        $this->assertTrue(CampagnePhoningResource::canAccess());
        $this->assertTrue(ScriptAppelResource::canAccess());
        $this->assertTrue(StatutPhoningResource::canAccess());
        $this->assertTrue(DossierFormationResource::canAccess());
        $this->assertFalse(ProspectResource::canAccess());
        $this->assertFalse(TicketResource::canAccess());
    }

    #[Test]
    public function selective_allopro_role_only_accesses_ticket_module(): void
    {
        $role = Role::create(['name' => 'test_selective_allopro', 'guard_name' => 'web']);
        $role->syncPermissions([
            'tickets.create',
            'tickets.view',
            'tickets.update_statut',
        ]);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $this->assertTrue(TicketResource::canAccess());
        $this->assertTrue(TicketResource::canCreate());
        $this->assertFalse(ProspectResource::canAccess());
        $this->assertFalse(PartenaireResource::canAccess());
        $this->assertFalse(ClientResource::canAccess());
    }

    #[Test]
    public function knowledge_base_permissions_follow_cdc_read_and_edit_profiles(): void
    {
        $record = DocumentKnowledge::make(['titre' => 'Procedure test']);

        $readerRole = Role::create(['name' => 'test_knowledge_reader', 'guard_name' => 'web']);
        $readerRole->syncPermissions([
            'document_knowledges.view_any',
            'document_knowledges.view',
        ]);

        $this->actingAs($this->userWithRole($readerRole));

        $this->assertTrue(DocumentKnowledgeResource::canAccess());
        $this->assertTrue(DocumentKnowledgeResource::canView($record));
        $this->assertFalse(DocumentKnowledgeResource::canCreate());
        $this->assertFalse(DocumentKnowledgeResource::canEdit($record));
        $this->assertFalse(DocumentKnowledgeResource::canDelete($record));

        $editorRole = Role::create(['name' => 'test_knowledge_editor', 'guard_name' => 'web']);
        $editorRole->syncPermissions([
            'document_knowledges.view_any',
            'document_knowledges.view',
            'document_knowledges.create',
            'document_knowledges.update',
            'document_knowledges.delete',
        ]);

        $this->actingAs($this->userWithRole($editorRole));

        $this->assertTrue(DocumentKnowledgeResource::canAccess());
        $this->assertTrue(DocumentKnowledgeResource::canCreate());
        $this->assertTrue(DocumentKnowledgeResource::canEdit($record));
        $this->assertTrue(DocumentKnowledgeResource::canDelete($record));

        $commercialRole = Role::create(['name' => 'test_knowledge_denied', 'guard_name' => 'web']);
        $this->actingAs($this->userWithRole($commercialRole));

        $this->assertFalse(DocumentKnowledgeResource::canAccess());
        $this->assertFalse(DocumentKnowledgeResource::canView($record));
    }

    #[Test]
    public function catalog_groups_permissions_by_entity_or_module(): void
    {
        $groups = AccessRightsCatalog::groupedPermissionOptions();
        $flatOptions = AccessRightsCatalog::permissionOptions();
        $fieldOptions = AccessRightsCatalog::fieldPermissionOptions();

        $this->assertArrayHasKey('AOPIA - Prospects', $groups);
        $this->assertArrayHasKey('AOPIA - Opportunites', $groups);
        $this->assertArrayHasKey('AOPIA - Rendez-vous', $groups);
        $this->assertArrayHasKey('AOPIA - Entreprises', $groups);
        $this->assertArrayHasKey('AOPIA - Campagnes phoning', $groups);
        $this->assertArrayHasKey('AOPIA - Base de connaissances', $groups);
        $this->assertArrayHasKey('AlloPro - Tickets', $groups);
        $this->assertArrayHasKey('prospects.view_any', $groups['AOPIA - Prospects']);
        $this->assertArrayHasKey('opportunites.view_any', $groups['AOPIA - Opportunites']);
        $this->assertArrayHasKey('rendez_vous.view_any', $groups['AOPIA - Rendez-vous']);
        $this->assertArrayHasKey('entreprises.view_any', $groups['AOPIA - Entreprises']);
        $this->assertArrayHasKey('campagne_phonings.view_any', $groups['AOPIA - Campagnes phoning']);
        $this->assertArrayHasKey('document_knowledges.view_any', $groups['AOPIA - Base de connaissances']);
        $this->assertArrayHasKey('tickets.update_statut', $groups['AlloPro - Tickets']);
        $this->assertSame('AOPIA - Prospects - Lister', $flatOptions['prospects.view_any']);
        $this->assertSame('AOPIA - Opportunites - Lister', $flatOptions['opportunites.view_any']);
        $this->assertSame('AOPIA - Rendez-vous - Lister', $flatOptions['rendez_vous.view_any']);
        $this->assertSame('AOPIA - Base de connaissances - Voir', $flatOptions['document_knowledges.view']);
        $this->assertSame('AlloPro - Tickets - Modifier le statut', $flatOptions['tickets.update_statut']);
        $this->assertSame('AOPIA - Prospects - Nom - Voir', $fieldOptions['fields.prospects.nom.show']);
        $this->assertSame('AOPIA - Opportunites - Nom entite - Voir', $fieldOptions['fields.opportunites.nom_entite.show']);
        $this->assertSame('AOPIA - Rendez-vous - Date et heure - Modifier', $fieldOptions['fields.rendez_vous.date_heure.edit']);
        $this->assertSame('AOPIA - Entreprises - Raison sociale - Tout', $fieldOptions['fields.entreprises.raison_sociale.all']);
        $this->assertSame('AOPIA - Prospects - Nom - Tout', $fieldOptions['fields.prospects.nom.all']);
        $this->assertSame('AlloPro - Tickets - Statut - Flux', $fieldOptions['fields.tickets.statut.flux']);
        $this->assertTrue(
            collect(AccessRightsCatalog::allPermissionNames())
                ->every(fn (string $permission) => Permission::where('name', $permission)->exists())
        );
    }

    #[Test]
    public function field_permissions_support_show_create_edit_flux_and_all_actions(): void
    {
        $role = Role::create(['name' => 'test_field_rights', 'guard_name' => 'web']);
        $role->syncPermissions([
            'fields.prospects.nom.all',
            'fields.prospects.email.show',
        ]);

        $user = $this->userWithRole($role);

        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'prospects', 'nom', 'show'));
        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'prospects', 'nom', 'create'));
        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'prospects', 'nom', 'edit'));
        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'prospects', 'nom', 'flux'));
        $this->assertTrue(AccessRightsCatalog::userCanField($user, 'prospects', 'email', 'show'));
        $this->assertFalse(AccessRightsCatalog::userCanField($user, 'prospects', 'email', 'edit'));
        $this->assertFalse(AccessRightsCatalog::userCanField($user, 'prospects', 'ville', 'show'));
    }

    #[Test]
    public function field_permission_filter_removes_forbidden_create_and_edit_data(): void
    {
        $role = Role::create(['name' => 'test_field_filter', 'guard_name' => 'web']);
        $role->syncPermissions([
            'fields.prospects.nom.create',
            'fields.prospects.email.edit',
        ]);

        $user = $this->userWithRole($role);

        $createData = AccessRightsCatalog::filterFieldDataForUser($user, 'prospects', [
            'nom' => 'AOPIA',
            'email' => 'contact@example.test',
            'ville' => 'Paris',
            '_token' => 'kept',
        ], 'create');

        $editData = AccessRightsCatalog::filterFieldDataForUser($user, 'prospects', [
            'nom' => 'AOPIA',
            'email' => 'contact@example.test',
            'ville' => 'Paris',
            '_token' => 'kept',
        ], 'edit');

        $this->assertSame([
            'nom' => 'AOPIA',
            '_token' => 'kept',
        ], $createData);

        $this->assertSame([
            'email' => 'contact@example.test',
            '_token' => 'kept',
        ], $editData);
    }

    #[Test]
    public function show_field_permissions_hide_known_forbidden_filament_components(): void
    {
        $role = Role::create(['name' => 'test_show_field_components', 'guard_name' => 'web']);
        $role->syncPermissions([
            'fields.prospects.nom.show',
        ]);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $resourceClass = get_class(new class {
            use \App\Support\UsesResourcePermissions;

            protected static string $permissionPrefix = 'prospects';
        });

        [$allowedColumn, $forbiddenColumn, $mappedColumn, $unknownColumn, $alreadyHiddenColumn, $nestedSection] = $resourceClass::applyShowFieldPermissions([
            TextColumn::make('nom'),
            TextColumn::make('email'),
            TextColumn::make('teleprospecteur.nom'),
            TextColumn::make('computed_metric'),
            TextColumn::make('nom')->visible(false),
            Section::make('Contact')->schema([
                TextEntry::make('email'),
            ]),
        ], [
            'teleprospecteur.nom' => 'teleprospecteur_id',
        ]);

        $this->assertFalse($allowedColumn->isHidden());
        $this->assertTrue($forbiddenColumn->isHidden());
        $this->assertTrue($mappedColumn->isHidden());
        $this->assertFalse($unknownColumn->isHidden());
        $this->assertTrue($alreadyHiddenColumn->isHidden());
        $this->assertTrue($nestedSection->getChildComponents()[0]->isHidden());
    }

    #[Test]
    public function show_field_permissions_resolve_relation_entries_to_foreign_keys(): void
    {
        $role = Role::create(['name' => 'test_show_field_relations', 'guard_name' => 'web']);
        $role->syncPermissions([
            'fields.prospects.commercial_id.show',
        ]);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $resourceClass = get_class(new class {
            use \App\Support\UsesResourcePermissions;

            protected static string $permissionPrefix = 'prospects';
        });

        [$commercialColumn, $teleprospecteurColumn, $validatorColumn] = $resourceClass::applyShowFieldPermissions([
            TextColumn::make('commercial.nom'),
            TextColumn::make('teleprospecteur.nom'),
            TextColumn::make('validePar.nom'),
        ]);

        $this->assertFalse($commercialColumn->isHidden());
        $this->assertTrue($teleprospecteurColumn->isHidden());
        $this->assertTrue($validatorColumn->isHidden());
    }

    #[Test]
    public function form_field_permissions_apply_create_edit_show_and_all_rules_by_role(): void
    {
        $role = Role::create(['name' => 'test_form_field_components', 'guard_name' => 'web']);
        $role->syncPermissions([
            'fields.prospects.nom.create',
            'fields.prospects.email.show',
            'fields.prospects.telephone.edit',
            'fields.prospects.siret.all',
        ]);

        $user = $this->userWithRole($role);
        $this->actingAs($user);

        $resourceClass = get_class(new class {
            use \App\Support\UsesResourcePermissions;

            protected static string $permissionPrefix = 'prospects';
        });

        $this->assertFalse($resourceClass::shouldHideFormField('nom', 'create'));
        $this->assertTrue($resourceClass::shouldHideFormField('email', 'create'));

        $this->assertFalse($resourceClass::shouldHideFormField('email', 'edit'));
        $this->assertTrue($resourceClass::shouldDisableFormField('email', 'edit'));

        $this->assertFalse($resourceClass::shouldHideFormField('telephone', 'edit'));
        $this->assertFalse($resourceClass::shouldDisableFormField('telephone', 'edit'));

        $this->assertFalse($resourceClass::shouldHideFormField('siret', 'create'));
        $this->assertFalse($resourceClass::shouldDisableFormField('siret', 'edit'));

        $this->assertFalse($resourceClass::shouldHideFormField('computed_metric', 'create'));
        $this->assertFalse($resourceClass::shouldHideFormField('email', null));

        [$lockedComponent] = $resourceClass::applyFormFieldPermissions([
            \Filament\Forms\Components\TextInput::make('email')->disabled(),
        ]);

        $disabledCondition = new \ReflectionProperty($lockedComponent, 'isDisabled');
        $disabledCondition->setAccessible(true);

        $this->assertTrue($disabledCondition->getValue($lockedComponent));
    }

    private function userWithRole(Role $role): User
    {
        $user = User::factory()->create([
            'role_cache' => $role->name,
            'actif' => true,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
