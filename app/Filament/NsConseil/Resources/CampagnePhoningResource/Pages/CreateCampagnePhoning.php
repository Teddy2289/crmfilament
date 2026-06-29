<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCampagnePhoning extends CreateRecord
{
    protected static string $resource = CampagnePhoningResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return CampagnePhoningResource::filterFormDataForFieldPermissions($data, 'create');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
