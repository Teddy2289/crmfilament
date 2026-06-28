<?php

namespace App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDocumentKnowledge extends CreateRecord
{
    protected static string $resource = DocumentKnowledgeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = DocumentKnowledgeResource::enrichFileMetadata($data);
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
