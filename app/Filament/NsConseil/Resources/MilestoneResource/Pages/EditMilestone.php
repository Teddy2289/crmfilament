<?php

namespace App\Filament\NsConseil\Resources\MilestoneResource\Pages;

use App\Filament\NsConseil\Resources\MilestoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMilestone extends EditRecord
{
    protected static string $resource = MilestoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
