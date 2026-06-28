<?php

namespace App\Filament\NsConseil\Resources\DocumentKnowledgeResource\Pages;

use App\Filament\NsConseil\Resources\DocumentKnowledgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDocumentKnowledge extends EditRecord
{
    protected static string $resource = DocumentKnowledgeResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = DocumentKnowledgeResource::enrichFileMetadata($data);
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
