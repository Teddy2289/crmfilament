<?php

namespace App\Filament\SuperAdmin\Resources\EnvSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\EnvSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEnvSettings extends ListRecords
{
    protected static string $resource = EnvSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
