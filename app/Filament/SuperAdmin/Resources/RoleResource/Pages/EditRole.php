<?php

namespace App\Filament\SuperAdmin\Resources\RoleResource\Pages;

use App\Filament\SuperAdmin\Resources\RoleResource;
use App\Support\AccessRightsCatalog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected ?string $accessMode = null;

    /**
     * @var array<int, string>
     */
    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['access_mode'] = RoleResource::accessModeFor($this->record);
        $data['module_permissions'] = AccessRightsCatalog::roleModulePermissionNames($this->record);
        $data['field_permissions'] = AccessRightsCatalog::roleFieldPermissionNames($this->record);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->accessMode = $data['access_mode'] ?? 'selective';
        $this->selectedPermissions = array_values(array_unique([
            ...($data['module_permissions'] ?? []),
            ...($data['field_permissions'] ?? []),
        ]));

        unset($data['access_mode'], $data['module_permissions'], $data['field_permissions']);

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->accessMode === 'all') {
            AccessRightsCatalog::syncFullAccess($this->record);

            return;
        }

        AccessRightsCatalog::syncSelectiveAccess($this->record, $this->selectedPermissions);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! in_array($this->record->name, ['super_admin', 'administrateur'])),
        ];
    }
}
