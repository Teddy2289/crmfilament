<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampagnesPhonings extends ListRecords
{
    protected static string $resource = CampagnePhoningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle campagne'),
        ];
    }
}
