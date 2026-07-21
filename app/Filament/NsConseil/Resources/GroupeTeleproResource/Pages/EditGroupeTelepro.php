<?php

namespace App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages;

use App\Filament\NsConseil\Resources\GroupeTeleproResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGroupeTelepro extends EditRecord
{
    protected static string $resource = GroupeTeleproResource::class;

    protected array $membres = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->membres = $data['membres'] ?? [];
        unset($data['membres']);

        return GroupeTeleproResource::filterFormDataForFieldPermissions($data, 'edit');
    }

    protected function afterSave(): void
    {
        $this->record->membres()->sync($this->membres);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
