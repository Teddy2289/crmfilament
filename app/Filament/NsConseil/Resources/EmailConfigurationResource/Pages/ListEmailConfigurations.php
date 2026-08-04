<?php

namespace App\Filament\NsConseil\Resources\EmailConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\EmailConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmailConfigurations extends ListRecords
{
    protected static string $resource = EmailConfigurationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvelle configuration'),
        ];
    }
}
