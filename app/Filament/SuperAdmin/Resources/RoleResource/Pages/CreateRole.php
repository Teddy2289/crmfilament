<?php

namespace App\Filament\SuperAdmin\Resources\RoleResource\Pages;

use App\Filament\SuperAdmin\Resources\RoleResource;
use App\Support\AccessRightsCatalog;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected ?string $accessMode = null;

    /**
     * @var array<int, string>
     */
    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->accessMode = $data['access_mode'] ?? 'selective';
        $this->selectedPermissions = array_values(array_unique([
            ...($data['module_permissions'] ?? []),
            ...($data['field_permissions'] ?? []),
        ]));

        unset($data['access_mode'], $data['module_permissions'], $data['field_permissions']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->accessMode === 'all') {
            AccessRightsCatalog::syncFullAccess($this->record);

            return;
        }

        AccessRightsCatalog::syncSelectiveAccess($this->record, $this->selectedPermissions);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
