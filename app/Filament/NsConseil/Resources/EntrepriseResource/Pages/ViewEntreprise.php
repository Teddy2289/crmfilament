<?php

namespace App\Filament\NsConseil\Resources\EntrepriseResource\Pages;

use App\Filament\NsConseil\Resources\EntrepriseResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEntreprise extends ViewRecord
{
    protected static string $resource = EntrepriseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
