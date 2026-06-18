<?php

namespace App\Filament\Allopro\Resources\ArtisanResource\Pages;

use App\Filament\Allopro\Resources\ArtisanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArtisan extends EditRecord
{
    protected static string $resource = ArtisanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Artisan mis à jour';
    }
}
