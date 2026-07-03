<?php

namespace App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages;

use App\Filament\SuperAdmin\Resources\CrmSettingResource;
use App\Services\Crm\CrmSettingsService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrmSettings extends ListRecords
{
    protected static string $resource = CrmSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('vider_cache')
                ->label('Vider le cache')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => app(CrmSettingsService::class)->forget())
                ->successNotificationTitle('Cache des paramètres CRM vidé'),

            Actions\CreateAction::make()
                ->label('Nouveau paramètre'),
        ];
    }
}
