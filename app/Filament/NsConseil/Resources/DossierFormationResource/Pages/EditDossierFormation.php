<?php

namespace App\Filament\NsConseil\Resources\DossierFormationResource\Pages;

use App\Filament\NsConseil\Resources\DossierFormationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDossierFormation extends EditRecord
{
    protected static string $resource = DossierFormationResource::class;

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
        return $this->getResource()::getUrl('index');
    }
}
