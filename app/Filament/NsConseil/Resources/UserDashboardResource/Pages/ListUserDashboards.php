<?php

namespace App\Filament\NsConseil\Resources\UserDashboardResource\Pages;

use App\Filament\NsConseil\Resources\UserDashboardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserDashboards extends ListRecords
{
    protected static string $resource = UserDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
