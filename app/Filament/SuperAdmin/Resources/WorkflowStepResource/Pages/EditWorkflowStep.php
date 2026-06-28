<?php

namespace App\Filament\SuperAdmin\Resources\WorkflowStepResource\Pages;

use App\Filament\SuperAdmin\Resources\WorkflowStepResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkflowStep extends EditRecord
{
    protected static string $resource = WorkflowStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
