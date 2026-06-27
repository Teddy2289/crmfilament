<?php

namespace App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentKnowledge extends ViewRecord
{
    protected static string $resource = DocumentKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
