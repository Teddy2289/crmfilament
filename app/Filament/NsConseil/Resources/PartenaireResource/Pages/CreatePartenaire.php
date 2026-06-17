<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Filament\NsConseil\Resources\PartenaireResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePartenaire extends CreateRecord
{
    protected static string $resource = PartenaireResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Forcer date_modification_statut à la création
        $data['date_modification_statut'] = now();

        return $data;
    }
}
