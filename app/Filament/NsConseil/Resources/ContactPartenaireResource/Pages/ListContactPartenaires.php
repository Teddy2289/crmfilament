<?php

namespace App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages;

use App\Filament\NsConseil\Resources\ContactPartenaireResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContactPartenaires extends ListRecords
{
    protected static string $resource = ContactPartenaireResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
