<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Traits\HasResponsiveForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePartenaire extends CreateRecord
{
    use HasResponsiveForm;

    protected static string $resource = PartenaireResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && auth()->user()?->actif;
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = PartenaireResource::filterFormDataForFieldPermissions($data, 'create');

        // Forcer date_modification_statut à la création
        $data['date_modification_statut'] = now();

        return $data;
    }
}
