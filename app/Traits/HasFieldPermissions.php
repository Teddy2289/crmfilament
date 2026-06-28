<?php

namespace App\Traits;

use App\Services\Crm\PermissionService;
use Filament\Forms\Components\Component;
use Filament\Tables\Columns\Column;
use Illuminate\Support\Facades\Auth;

trait HasFieldPermissions
{
    protected string $resourceName = '';

    protected PermissionService $permissionService;

    protected function initializeFieldPermissions(): void
    {
        $this->permissionService = new PermissionService();
        $this->permissionService->setResource($this->resourceName);
    }

    protected function setResourceName(string $name): void
    {
        $this->resourceName = $name;
        $this->initializeFieldPermissions();
    }

    protected function canViewField(string $field, string $context = 'list'): bool
    {
        return $this->permissionService->canViewField($field, $context);
    }

    protected function isFieldReadOnly(string $field): bool
    {
        return $this->permissionService->isFieldReadOnly($field);
    }

    protected function applyFieldPermissions(array $components, string $context = 'list'): array
    {
        return array_filter($components, function ($component) use ($context) {
            $fieldName = $this->extractFieldName($component);
            
            if (! $fieldName) {
                return true;
            }

            return $this->canViewField($fieldName, $context);
        });
    }

    protected function applyReadOnlyPermissions(array $components): array
    {
        foreach ($components as $component) {
            $fieldName = $this->extractFieldName($component);
            
            if ($fieldName && $this->isFieldReadOnly($fieldName)) {
                if ($component instanceof Component) {
                    $component->disabled();
                }
            }
        }

        return $components;
    }

    protected function extractFieldName($component): ?string
    {
        if ($component instanceof Column) {
            return $component->getName();
        }

        if ($component instanceof Component) {
            return $component->getName();
        }

        return null;
    }

    protected function filterColumnsByPermission(array $columns): array
    {
        return $this->applyFieldPermissions($columns, 'list');
    }

    protected function filterFormFieldsByPermission(array $fields): array
    {
        $visibleFields = $this->applyFieldPermissions($fields, 'edit');
        
        return $this->applyReadOnlyPermissions($visibleFields);
    }

    protected function filterInfolistEntriesByPermission(array $entries): array
    {
        return $this->applyFieldPermissions($entries, 'view');
    }
}
