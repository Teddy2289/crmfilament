<?php

namespace App\Filament\NsConseil\Resources\HistoriqueModificationResource\Pages;

use App\Filament\NsConseil\Resources\HistoriqueModificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHistoriqueModifications extends ListRecords
{
    protected static string $resource = HistoriqueModificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pas de création manuelle (lecture seule)
        ];
    }
}
