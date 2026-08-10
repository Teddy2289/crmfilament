<?php

namespace App\Filament\NsConseil\Resources\DataDeletionRequestResource\Pages;

use App\Filament\NsConseil\Resources\DataDeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDataDeletionRequest extends EditRecord
{
    protected static string $resource = DataDeletionRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
