<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Filament\NsConseil\Resources\PartenaireResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Models\Partenaire;

class EditPartenaire extends EditRecord
{
    use HasRoleAccess;
    protected static string $resource = PartenaireResource::class;

    /**
     * CDC §6 — Édition partenaire :
     * - Admin / Superviseur : accès total
     * - Commercial : uniquement s'il est affecté sur la fiche
     * - TP : interdit
     */
    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isAdmin() || $user->isSuperviseur()) {
            return true;
        }

        if ($user->isCommercial() && isset($parameters['record'])) {
            $record = $parameters['record'];
            if ($record instanceof Partenaire) {
                return $record->commercial_id === $user->id;
            }

            $partenaire = Partenaire::find($record);

            return $partenaire && $partenaire->commercial_id === $user->id;
        }

        return false;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return PartenaireResource::filterFormDataForFieldPermissions($data, 'edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
            Actions\ForceDeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
