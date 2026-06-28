<?php

namespace App\Services\Crm;

use App\Models\FieldPermission;
use Illuminate\Support\Facades\Auth;

class PermissionService
{
    protected string $resource = '';

    public function setResource(string $resource): self
    {
        $this->resource = $resource;

        return $this;
    }

    protected function getUserRole(): string
    {
        $user = Auth::user();
        if (! $user) {
            return 'guest';
        }

        return $user->role_cache ?? 'guest';
    }

    public function canViewField(string $field, string $context = 'list'): bool
    {
        $role = $this->getUserRole();

        return FieldPermission::canViewField($role, $this->resource, $field, $context);
    }

    public function isFieldReadOnly(string $field): bool
    {
        $role = $this->getUserRole();

        return FieldPermission::isFieldReadOnly($role, $this->resource, $field);
    }

    public function getFieldPermissionsForRole(string $role): array
    {
        return FieldPermission::where('role', $role)
            ->where('resource', $this->resource)
            ->get()
            ->keyBy('field_name')
            ->toArray();
    }

    public function setFieldPermission(array $data): FieldPermission
    {
        return FieldPermission::updateOrCreate(
            [
                'role' => $data['role'],
                'resource' => $this->resource,
                'field_name' => $data['field_name'],
            ],
            [
                'visible_list' => $data['visible_list'] ?? true,
                'visible_view' => $data['visible_view'] ?? true,
                'visible_edit' => $data['visible_edit'] ?? true,
                'read_only' => $data['read_only'] ?? false,
            ]
        );
    }

    public function deleteFieldPermission(string $role, string $field): bool
    {
        return FieldPermission::where('role', $role)
            ->where('resource', $this->resource)
            ->where('field_name', $field)
            ->delete() > 0;
    }

    public function getFieldsForContext(string $context): array
    {
        $role = $this->getUserRole();
        $permissions = $this->getFieldPermissionsForRole($role);

        return array_filter($permissions, function ($permission) use ($context) {
            return match ($context) {
                'list' => $permission['visible_list'],
                'view' => $permission['visible_view'],
                'edit' => $permission['visible_edit'],
                default => true,
            };
        });
    }

    public function getReadOnlyFields(): array
    {
        $role = $this->getUserRole();
        $permissions = $this->getFieldPermissionsForRole($role);

        return array_keys(array_filter($permissions, fn ($p) => $p['read_only']));
    }
}
