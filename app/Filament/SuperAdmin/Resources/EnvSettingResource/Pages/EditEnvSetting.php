<?php

namespace App\Filament\SuperAdmin\Resources\EnvSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\EnvSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnvSetting extends EditRecord
{
    protected static string $resource = EnvSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
