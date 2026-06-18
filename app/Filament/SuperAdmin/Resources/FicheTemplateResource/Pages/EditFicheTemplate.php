<?php

namespace App\Filament\SuperAdmin\Resources\FicheTemplateResource\Pages;

use App\Filament\SuperAdmin\Resources\FicheTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFicheTemplate extends EditRecord
{
    protected static string $resource = FicheTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
