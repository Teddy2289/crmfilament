<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Filament\NsConseil\Resources\ProspectResource;
use App\Traits\HasResponsiveForm;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProspect extends EditRecord
{
    use HasResponsiveForm;

    protected static string $resource = ProspectResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ProspectResource::filterFormDataForFieldPermissions($data, 'edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
