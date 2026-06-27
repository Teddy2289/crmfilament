<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Pages;

use App\Filament\NsConseil\Resources\ClientResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ClientResource::filterFormDataForFieldPermissions($data, 'create');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
