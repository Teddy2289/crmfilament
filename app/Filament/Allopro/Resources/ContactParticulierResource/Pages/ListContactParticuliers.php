<?php

namespace App\Filament\Allopro\Resources\ContactParticulierResource\Pages;

use App\Filament\Allopro\Resources\ContactParticulierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContactParticuliers extends ListRecords
{
    protected static string $resource = ContactParticulierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
