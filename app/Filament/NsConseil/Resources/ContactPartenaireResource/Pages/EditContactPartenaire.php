<?php

namespace App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages;

use App\Filament\NsConseil\Resources\ContactPartenaireResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContactPartenaire extends EditRecord
{
    protected static string $resource = ContactPartenaireResource::class;

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
