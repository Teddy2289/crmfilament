<?php

namespace App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages;

use App\Filament\NsConseil\Resources\GroupeTeleproResource;
use App\Models\User;
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
        User::where('groupe_telepro_id', $this->record->id)
            ->whereNotIn('id', $this->membres === [] ? [0] : $this->membres)
            ->update(['groupe_telepro_id' => null]);

        if ($this->membres !== []) {
            User::whereIn('id', $this->membres)->update(['groupe_telepro_id' => $this->record->id]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
