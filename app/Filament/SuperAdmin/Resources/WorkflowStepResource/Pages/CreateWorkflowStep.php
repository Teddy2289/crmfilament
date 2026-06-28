<?php

namespace App\Filament\SuperAdmin\Resources\WorkflowStepResource\Pages;

use App\Filament\SuperAdmin\Resources\WorkflowStepResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkflowStep extends CreateRecord
{
    protected static string $resource = WorkflowStepResource::class;
}
