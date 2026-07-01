<?php

namespace App\Filament\NsConseil\Resources\ActivitePermanenceResource\Pages;

use App\Filament\NsConseil\Resources\ActivitePermanenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivitePermanences extends ListRecords
{
    protected static string $resource = ActivitePermanenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
