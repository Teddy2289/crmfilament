<?php

namespace App\Filament\NsConseil\Resources\AnalyticDashboardResource\Pages;

use App\Filament\NsConseil\Resources\AnalyticDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnalyticDashboards extends ListRecords
{
    protected static string $resource = AnalyticDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
