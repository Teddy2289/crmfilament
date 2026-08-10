<?php

namespace App\Filament\NsConseil\Resources\GanttTaskResource\Pages;

use App\Filament\NsConseil\Resources\GanttTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGanttTask extends EditRecord
{
    protected static string $resource = GanttTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
