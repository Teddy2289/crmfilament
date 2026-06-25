<?php

namespace App\Filament\NsConseil\Resources\ContactPartenaireResource\Pages;

use App\Filament\NsConseil\Resources\ContactPartenaireResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContactPartenaire extends CreateRecord
{
    protected static string $resource = ContactPartenaireResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
