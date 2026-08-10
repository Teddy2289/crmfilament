<?php

namespace App\Filament\NsConseil\Resources\KpiResource\Pages;

use App\Filament\NsConseil\Resources\KpiResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKpi extends EditRecord
{
    protected static string $resource = KpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
