<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Filament\NsConseil\Resources\ProspectResource;
use App\Traits\HasResponsiveForm;
use Filament\Resources\Pages\CreateRecord;

class CreateProspect extends CreateRecord
{
    use HasResponsiveForm;

    protected static string $resource = ProspectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ProspectResource::filterFormDataForFieldPermissions($data, 'create');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
