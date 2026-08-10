<?php

namespace Tests\Unit\Api\Services;

use App\Models\FieldPermission;
use App\Services\Api\FieldPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitaires pour FieldPermissionService.
 *
 * Couvre : filterFields — comportement par défaut, masquage de champs, actions view/edit
 * Requirements : 3.4, 12.4
 */
class FieldPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private FieldPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FieldPermissionService();
    }

    // -------------------------------------------------------------------------
    // Comportement par défaut (aucun enregistrement FieldPermission)
    // -------------------------------------------------------------------------

    public function test_returns_all_fields_when_no_permission_records_exist(): void
    {
        $data = ['nom' => 'Acme', 'siret' => '12345678901234', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'commercial', 'prospects', 'view');

        $this->assertSame($data, $result);
    }

    public function test_returns_all_fields_for_unknown_role_when_no_records_exist(): void
    {
        $data = ['id' => 1, 'nom' => 'Test'];

        $result = $this->service->filterFields($data, 'role_inconnu', 'prospects', 'view');

        $this->assertSame($data, $result);
    }

    public function test_returns_all_fields_for_unknown_resource_when_no_records_exist(): void
    {
        $data = ['id' => 1, 'field' => 'value'];

        $result = $this->service->filterFields($data, 'commercial', 'ressource_inconnue', 'view');

        $this->assertSame($data, $result);
    }

    public function test_returns_empty_array_when_data_is_empty(): void
    {
        $result = $this->service->filterFields([], 'commercial', 'prospects', 'view');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // Masquage de champs — action 'view' (visible_view)
    // -------------------------------------------------------------------------

    public function test_removes_field_when_visible_view_is_false(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);

        $data = ['nom' => 'Acme', 'siret' => '12345678901234', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'view');

        $this->assertArrayNotHasKey('siret', $result);
        $this->assertArrayHasKey('nom', $result);
        $this->assertArrayHasKey('email', $result);
    }

    public function test_keeps_field_when_visible_view_is_true(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'nom',
            'visible_view' => true,
        ]);

        $data = ['nom' => 'Acme', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'view');

        $this->assertArrayHasKey('nom', $result);
    }

    public function test_removes_multiple_hidden_fields(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'operateur_n1',
            'resource'     => 'prospects',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);
        FieldPermission::factory()->create([
            'role'         => 'operateur_n1',
            'resource'     => 'prospects',
            'field_name'   => 'telephone',
            'visible_view' => false,
        ]);

        $data = ['nom' => 'Acme', 'siret' => '12345678901234', 'telephone' => '0600000000'];

        $result = $this->service->filterFields($data, 'operateur_n1', 'prospects', 'view');

        $this->assertArrayNotHasKey('siret', $result);
        $this->assertArrayNotHasKey('telephone', $result);
        $this->assertArrayHasKey('nom', $result);
    }

    public function test_keeps_fields_not_in_permission_table(): void
    {
        // Only 'siret' has a permission record; other fields default to visible
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);

        $data = ['nom' => 'Acme', 'siret' => '123', 'ville' => 'Paris', 'email' => 'a@b.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'view');

        $this->assertArrayNotHasKey('siret', $result);
        $this->assertArrayHasKey('nom', $result);
        $this->assertArrayHasKey('ville', $result);
        $this->assertArrayHasKey('email', $result);
    }

    // -------------------------------------------------------------------------
    // Masquage de champs — action 'edit' (visible_edit)
    // -------------------------------------------------------------------------

    public function test_removes_field_when_visible_edit_is_false_for_edit_action(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'email',
            'visible_view' => true,
            'visible_edit' => false,
        ]);

        $data = ['nom' => 'Acme', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'edit');

        $this->assertArrayNotHasKey('email', $result);
        $this->assertArrayHasKey('nom', $result);
    }

    public function test_update_action_uses_visible_edit_column(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'email',
            'visible_view' => true,
            'visible_edit' => false,
        ]);

        $data = ['nom' => 'Acme', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'update');

        $this->assertArrayNotHasKey('email', $result);
    }

    public function test_store_action_uses_visible_edit_column(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'email',
            'visible_view' => true,
            'visible_edit' => false,
        ]);

        $data = ['nom' => 'Acme', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'store');

        $this->assertArrayNotHasKey('email', $result);
    }

    public function test_view_action_is_not_affected_by_visible_edit_false(): void
    {
        // visible_view = true, visible_edit = false
        // For action='view', the field should remain visible
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'email',
            'visible_view' => true,
            'visible_edit' => false,
        ]);

        $data = ['nom' => 'Acme', 'email' => 'contact@acme.fr'];

        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'view');

        $this->assertArrayHasKey('email', $result);
    }

    // -------------------------------------------------------------------------
    // Isolation par rôle et ressource
    // -------------------------------------------------------------------------

    public function test_permissions_for_different_role_do_not_affect_other_role(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'prospects',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);

        $data = ['nom' => 'Acme', 'siret' => '123'];

        // commercial has no FieldPermission → default visible
        $result = $this->service->filterFields($data, 'commercial', 'prospects', 'view');

        $this->assertArrayHasKey('siret', $result);
    }

    public function test_permissions_for_different_resource_do_not_apply(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'teleprospecteur',
            'resource'     => 'partenaires',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);

        $data = ['nom' => 'Acme', 'siret' => '123'];

        // prospects resource has no FieldPermission → default visible
        $result = $this->service->filterFields($data, 'teleprospecteur', 'prospects', 'view');

        $this->assertArrayHasKey('siret', $result);
    }

    // -------------------------------------------------------------------------
    // Cache interne (N+1 guard)
    // -------------------------------------------------------------------------

    public function test_second_call_with_same_params_uses_cache_and_returns_same_result(): void
    {
        FieldPermission::factory()->create([
            'role'         => 'commercial',
            'resource'     => 'prospects',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);

        $data = ['nom' => 'Acme', 'siret' => '123'];

        $result1 = $this->service->filterFields($data, 'commercial', 'prospects', 'view');
        $result2 = $this->service->filterFields($data, 'commercial', 'prospects', 'view');

        $this->assertSame($result1, $result2);
        $this->assertArrayNotHasKey('siret', $result1);
    }

    public function test_flush_cache_allows_re_querying(): void
    {
        // First call: no permissions → all fields visible
        $data = ['nom' => 'Acme', 'siret' => '123'];
        $result1 = $this->service->filterFields($data, 'commercial', 'prospects', 'view');
        $this->assertArrayHasKey('siret', $result1);

        // Add a permission after first call
        FieldPermission::factory()->create([
            'role'         => 'commercial',
            'resource'     => 'prospects',
            'field_name'   => 'siret',
            'visible_view' => false,
        ]);

        // Without flushing, cache still says all visible
        $result2 = $this->service->filterFields($data, 'commercial', 'prospects', 'view');
        $this->assertArrayHasKey('siret', $result2);

        // After flush, the new permission takes effect
        $this->service->flushCache();
        $result3 = $this->service->filterFields($data, 'commercial', 'prospects', 'view');
        $this->assertArrayNotHasKey('siret', $result3);
    }
}
