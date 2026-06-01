<?php

namespace App\Filament\Allopro\Resources\ContactParticulierResource\Pages;

use App\Filament\Allopro\Resources\ContactParticulierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactParticulier extends EditRecord
{
    protected static string $resource = ContactParticulierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
