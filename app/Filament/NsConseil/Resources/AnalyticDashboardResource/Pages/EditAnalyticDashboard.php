<?php

namespace App\Filament\NsConseil\Resources\AnalyticDashboardResource\Pages;

use App\Filament\NsConseil\Resources\AnalyticDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnalyticDashboard extends EditRecord
{
    protected static string $resource = AnalyticDashboardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
