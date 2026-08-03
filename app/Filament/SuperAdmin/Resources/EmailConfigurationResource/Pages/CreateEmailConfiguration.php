<?php

namespace App\Filament\SuperAdmin\Resources\EmailConfigurationResource\Pages;

use App\Filament\SuperAdmin\Resources\EmailConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailConfiguration extends CreateRecord
{
    protected static string $resource = EmailConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Tester automatiquement la connexion après création
        $result = $this->record->testConnection();
        
        if (! $result['success']) {
            // Si la connexion échoue, désactiver la configuration
            $this->record->deactivate();
        }
    }
}
