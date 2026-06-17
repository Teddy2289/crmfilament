<?php

namespace App\Filament\SuperAdmin\Resources\FicheTemplateResource\Pages;

use App\Filament\SuperAdmin\Resources\FicheTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFicheTemplates extends ListRecords
{
    protected static string $resource = FicheTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouveau modèle'),
        ];
    }
}
