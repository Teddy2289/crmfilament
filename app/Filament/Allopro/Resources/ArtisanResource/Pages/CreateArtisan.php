<?php
namespace App\Filament\Allopro\Resources\ArtisanResource\Pages;

use App\Filament\Allopro\Resources\ArtisanResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtisan extends CreateRecord
{
    protected static string $resource = ArtisanResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Artisan créé avec succès';
    }
}
