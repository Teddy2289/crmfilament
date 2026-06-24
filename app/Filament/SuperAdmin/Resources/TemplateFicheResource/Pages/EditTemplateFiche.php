<?php

namespace App\Filament\SuperAdmin\Resources\TemplateFicheResource\Pages;

use App\Filament\SuperAdmin\Resources\TemplateFicheResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplateFiche extends EditRecord
{
    protected static string $resource = TemplateFicheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
