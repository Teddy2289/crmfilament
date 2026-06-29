<?php

namespace App\Filament\NsConseil\Resources\EntrepriseResource\Pages;

use App\Filament\NsConseil\Resources\EntrepriseResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEntreprise extends CreateRecord
{
    protected static string $resource = EntrepriseResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return EntrepriseResource::filterFormDataForFieldPermissions($data, 'create');
    }
}
