<?php

namespace App\Filament\NsConseil\Resources\OpportuniteResource\Pages;

use App\Filament\NsConseil\Resources\OpportuniteResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOpportunite extends CreateRecord
{
    protected static string $resource = OpportuniteResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return OpportuniteResource::filterFormDataForFieldPermissions($data, 'create');
    }
}
