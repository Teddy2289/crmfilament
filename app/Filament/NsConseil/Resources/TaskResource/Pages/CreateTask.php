<?php

namespace App\Filament\NsConseil\Resources\TaskResource\Pages;

use App\Filament\NsConseil\Resources\TaskResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            Actions\Action::make('create_and_another')
                ->label('Créer et ajouter une autre')
                ->action('createAndAnother'),
        ];
    }
}
