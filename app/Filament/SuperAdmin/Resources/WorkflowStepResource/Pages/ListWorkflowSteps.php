<?php

namespace App\Filament\SuperAdmin\Resources\WorkflowStepResource\Pages;

use App\Filament\SuperAdmin\Resources\WorkflowStepResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWorkflowSteps extends ListRecords
{
    protected static string $resource = WorkflowStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
