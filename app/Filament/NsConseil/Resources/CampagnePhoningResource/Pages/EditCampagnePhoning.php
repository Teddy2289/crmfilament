<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCampagnePhoning extends EditRecord
{
    protected static string $resource = CampagnePhoningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
