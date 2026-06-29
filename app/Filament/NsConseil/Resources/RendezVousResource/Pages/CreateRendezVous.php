<?php

// ── app/Filament/NsConseil/Resources/RendezVousResource/Pages/CreateRendezVous.php

namespace App\Filament\NsConseil\Resources\RendezVousResource\Pages;

use App\Filament\NsConseil\Resources\RendezVousResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRendezVous extends CreateRecord
{
    protected static string $resource = RendezVousResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Si pas de commercial assigné, assigner l'utilisateur connecté
        if (empty($data['commercial_id'])) {
            $data['commercial_id'] = auth()->id();
        }

        return RendezVousResource::filterFormDataForFieldPermissions($data, 'create');
    }
}
