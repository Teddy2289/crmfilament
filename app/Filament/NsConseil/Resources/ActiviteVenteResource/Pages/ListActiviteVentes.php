<?php

namespace App\Filament\NsConseil\Resources\ActiviteVenteResource\Pages;

use App\Filament\NsConseil\Resources\ActiviteVenteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActiviteVentes extends ListRecords
{
    protected static string $resource = ActiviteVenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
