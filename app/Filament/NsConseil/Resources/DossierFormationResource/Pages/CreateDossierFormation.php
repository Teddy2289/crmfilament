<?php

namespace App\Filament\NsConseil\Resources\DossierFormationResource\Pages;

use App\Filament\NsConseil\Resources\DossierFormationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDossierFormation extends CreateRecord
{
    protected static string $resource = DossierFormationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
