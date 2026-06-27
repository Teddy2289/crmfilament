<?php

namespace App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentKnowledge extends EditRecord
{
    protected static string $resource = DocumentKnowledgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
