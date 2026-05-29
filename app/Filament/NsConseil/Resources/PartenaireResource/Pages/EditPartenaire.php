<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Filament\NsConseil\Resources\PartenaireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPartenaire extends EditRecord
{
    protected static string $resource = PartenaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
