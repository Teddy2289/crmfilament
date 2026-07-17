<?php

namespace App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages;

use App\Filament\NsConseil\Resources\GroupeTeleproResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateGroupeTelepro extends CreateRecord
{
    protected static string $resource = GroupeTeleproResource::class;

    protected array $membres = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->membres = $data['membres'] ?? [];
        unset($data['membres']);

        return GroupeTeleproResource::filterFormDataForFieldPermissions($data, 'create');
    }

    protected function afterCreate(): void
    {
        if ($this->membres !== []) {
            User::whereIn('id', $this->membres)->update(['groupe_telepro_id' => $this->record->id]);
        }
    }
}
