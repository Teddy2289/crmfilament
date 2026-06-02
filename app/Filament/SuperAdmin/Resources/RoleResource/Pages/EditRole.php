<?php
namespace App\Filament\SuperAdmin\Resources\RoleResource\Pages;

use App\Filament\SuperAdmin\Resources\RoleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn() => !in_array($this->record->name, ['super_admin', 'administrateur'])),
        ];
    }
}
