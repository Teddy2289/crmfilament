<?php

namespace App\Filament\NsConseil\Resources\StatutPhoningResource\Pages;

use App\Filament\NsConseil\Resources\StatutPhoningResource;
use Filament\Resources\Pages\CreateRecord;

class CreateStatutPhoning extends CreateRecord
{
    protected static string $resource = StatutPhoningResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return StatutPhoningResource::filterFormDataForFieldPermissions($data, 'create');
    }
}
