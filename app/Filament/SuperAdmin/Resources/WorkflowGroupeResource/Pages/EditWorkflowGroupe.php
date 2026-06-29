<?php

namespace App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\Pages;

use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Filament\Infolists\Components\ViewEntry;

class EditWorkflowGroupe extends EditRecord
{
    protected static string $resource = WorkflowGroupeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions personnalisées si nécessaire
        ];
    }

    protected function afterSave(): void
    {
        // Synchroniser les positions visuelles avec les ordres
    }
}
