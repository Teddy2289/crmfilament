<?php

namespace App\Filament\NsConseil\Resources\KpiResource\Pages;

use App\Filament\NsConseil\Resources\KpiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateKpi extends CreateRecord
{
    protected static string $resource = KpiResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        return $data;
    }
}
