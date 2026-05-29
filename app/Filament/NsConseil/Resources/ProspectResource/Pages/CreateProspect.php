<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Filament\NsConseil\Resources\ProspectResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProspect extends CreateRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
