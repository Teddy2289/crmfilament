<?php

namespace App\Filament\SuperAdmin\Resources\EmailConfigurationResource\Pages;

use App\Filament\SuperAdmin\Resources\EmailConfigurationResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditEmailConfiguration extends EditRecord
{
    protected static string $resource = EmailConfigurationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterSave(): void
    {
        // Si le mot de passe a changé, tester la connexion
        if ($this->record->wasChanged('password')) {
            $result = $this->record->testConnection();
            
            if ($result['success']) {
                Notification::make()
                    ->title('Connexion testée avec succès')
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Erreur de connexion: '.$result['message'])
                    ->danger()
                    ->send();
                
                // Désactiver la configuration si la connexion échoue
                $this->record->deactivate();
            }
        }
    }
}
