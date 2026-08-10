<?php

namespace App\Filament\NsConseil\Resources\AnalyticDashboardResource\Pages;

use App\Filament\NsConseil\Resources\AnalyticDashboardResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateAnalyticDashboard extends CreateRecord
{
    protected static string $resource = AnalyticDashboardResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }
}
