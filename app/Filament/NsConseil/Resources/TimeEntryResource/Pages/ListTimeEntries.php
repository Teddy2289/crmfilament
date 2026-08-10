<?php

namespace App\Filament\NsConseil\Resources\TimeEntryResource\Pages;

use App\Filament\NsConseil\Resources\TimeEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTimeEntries extends ListRecords
{
    protected static string $resource = TimeEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
