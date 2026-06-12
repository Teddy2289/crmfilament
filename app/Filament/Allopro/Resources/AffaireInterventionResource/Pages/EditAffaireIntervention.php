<?php

namespace App\Filament\Allopro\Resources\AffaireInterventionResource\Pages;

use App\Filament\Allopro\Resources\AffaireInterventionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAffaireIntervention extends EditRecord
{
    protected static string $resource = AffaireInterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Affaire mise à jour';
    }
}
