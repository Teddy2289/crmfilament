<?php
// ── app/Filament/NsConseil/Resources/RendezVousResource/Pages/EditRendezVous.php

namespace App\Filament\NsConseil\Resources\RendezVousResource\Pages;

use App\Filament\NsConseil\Resources\RendezVousResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRendezVous extends EditRecord
{
    protected static string $resource = RendezVousResource::class;

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
