<?php

namespace App\Filament\NsConseil\Resources\TaskResource\Pages;

use App\Filament\NsConseil\Resources\TaskResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
