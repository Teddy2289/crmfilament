<?php

namespace App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentKnowledge extends ListRecords
{
    protected static string $resource = DocumentKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
