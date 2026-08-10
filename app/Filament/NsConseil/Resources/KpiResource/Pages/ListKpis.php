<?php

namespace App\Filament\NsConseil\Resources\KpiResource\Pages;

use App\Filament\NsConseil\Resources\KpiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKpis extends ListRecords
{
    protected static string $resource = KpiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
