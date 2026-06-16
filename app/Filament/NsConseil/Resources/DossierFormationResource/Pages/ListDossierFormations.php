<?php

namespace App\Filament\NsConseil\Resources\DossierFormationResource\Pages;

use App\Filament\NsConseil\Resources\DossierFormationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDossierFormations extends ListRecords
{
    protected static string $resource = DossierFormationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau dossier')
                ->icon('heroicon-o-plus'),
        ];
    }
}
