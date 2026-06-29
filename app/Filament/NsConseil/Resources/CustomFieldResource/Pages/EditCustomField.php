<?php

namespace App\Filament\NsConseil\Resources\CustomFieldResource\Pages;

use App\Filament\NsConseil\Resources\CustomFieldResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomField extends EditRecord
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
