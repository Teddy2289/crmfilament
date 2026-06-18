<?php

namespace App\Filament\Allopro\Resources\AffaireInterventionResource\Pages;

use App\Filament\Allopro\Resources\AffaireInterventionResource;
use App\Models\AffaireIntervention;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAffaireIntervention extends CreateRecord
{
    protected static string $resource = AffaireInterventionResource::class;

    // Dans CreateAffaireIntervention.php
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Supprimez ou commentez cette ligne :
        // $data['reference'] = AffaireIntervention::genererReference();

        $data['operateur_dispatch_id'] = $data['operateur_dispatch_id'] ?? auth()->id();
        $data['date_notification_artisan'] = $data['date_notification_artisan'] ?? now()->toDateTimeString();

        if (! empty($data['ticket_id'])) {
            $data['numero_tentative'] = AffaireIntervention::where('ticket_id', $data['ticket_id'])->count() + 1;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Affaire créée : '.$this->getRecord()->reference;
    }

    protected function handleRecordCreation(array $data): Model
    {
        \Log::info('CreateAffaireIntervention data', $data);

        return parent::handleRecordCreation($data);
    }
}
