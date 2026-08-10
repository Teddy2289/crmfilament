<?php

namespace App\Filament\NsConseil\Resources\AnalyticDashboardResource\Pages;

use App\Filament\NsConseil\Resources\AnalyticDashboardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAnalyticDashboard extends ViewRecord
{
    protected static string $resource = AnalyticDashboardResource::class;

    protected static string $view = 'filament.ns-conseil.resources.analytic-dashboard.view';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
