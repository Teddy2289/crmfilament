<?php
namespace App\Filament\NsConseil\Resources\DocumentResource\Pages;

use App\Filament\NsConseil\Resources\DocumentResource;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make(),
        ];
    }
}