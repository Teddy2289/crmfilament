<?php

namespace App\Filament\Allopro\Resources\TicketResource\Pages;

use App\Filament\Allopro\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Resources\Pages\CreateRecord;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['operateur_id'] = $data['operateur_id'] ?? auth()->id();
        $data['date_creation'] = now();
        $data['reference'] = Ticket::genererReference();

        // Convertir les enums en valeurs string
        if (isset($data['statut']) && ! is_string($data['statut'])) {
            $data['statut'] = $data['statut']->value;
        }
        if (isset($data['niveau_priorite']) && ! is_string($data['niveau_priorite'])) {
            $data['niveau_priorite'] = $data['niveau_priorite']->value;
        }
        if (isset($data['corps_de_metier']) && ! is_string($data['corps_de_metier'])) {
            $data['corps_de_metier'] = $data['corps_de_metier']->value;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Ticket créé : '.$this->getRecord()->reference;
    }
}
