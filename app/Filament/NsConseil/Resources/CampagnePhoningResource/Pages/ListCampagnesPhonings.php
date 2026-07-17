<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCampagnesPhonings extends ListRecords
{
    protected static string $resource = CampagnePhoningResource::class;

    public function mount(): void
    {
        // Un téléprospecteur n'a pas besoin de gérer les campagnes : on
        // l'envoie directement sur son interface d'appels.
        if (auth()->user()?->hasRoleCache('teleprospecteur')) {
            $this->redirect(PhoningWorkflow::getUrl());

            return;
        }

        parent::mount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle campagne'),
        ];
    }
}
