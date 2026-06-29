<?php

namespace App\Filament\NsConseil\Resources\EntrepriseResource\Pages;

use App\Filament\NsConseil\Resources\EntrepriseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntreprises extends ListRecords
{
    protected static string $resource = EntrepriseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
