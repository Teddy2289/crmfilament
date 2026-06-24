<?php

namespace App\Filament\SuperAdmin\Resources\TemplateFicheResource\Pages;

use App\Filament\SuperAdmin\Resources\TemplateFicheResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplateFiches extends ListRecords
{
    protected static string $resource = TemplateFicheResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
