<?php

namespace App\Filament\NsConseil\Resources\EntrepriseResource\Pages;

use App\Filament\NsConseil\Resources\EntrepriseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEntreprise extends EditRecord
{
    protected static string $resource = EntrepriseResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return EntrepriseResource::filterFormDataForFieldPermissions($data, 'edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
