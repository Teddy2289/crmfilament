<?php
namespace App\Filament\NsConseil\Resources\DocumentResource\Pages;

use App\Filament\NsConseil\Resources\DocumentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // FileUpload a déjà stocké le fichier ; on complète les métadonnées manquantes
        $path = $data['path'];
        $fullPath = Storage::disk('public')->path($path);

        $data['nom_fichier'] = basename($path);
        $data['mime_type'] = Storage::disk('public')->mimeType($path);
        $data['taille'] = Storage::disk('public')->size($path);
        $data['uploaded_by'] = auth()->id();

        return $data;
    }
}