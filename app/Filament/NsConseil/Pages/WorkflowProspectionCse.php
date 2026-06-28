<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class WorkflowProspectionCse extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Workflow prospection CSE';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 5;

    protected static string $view = 'filament.ns-conseil.pages.workflow-prospection-cse';

    protected static ?string $slug = 'workflow-prospection-cse';

    public ?WorkflowGroupe $selectedGroupe = null;

    public Collection $workflowSteps;

    public ?int $selectedWorkflowGroupeId = null;

    public function mount(): void
    {
        $this->loadWorkflow();
    }

    public function loadWorkflow(): void
    {
        if ($this->selectedWorkflowGroupeId) {
            $this->selectedGroupe = WorkflowGroupe::find($this->selectedWorkflowGroupeId);
            $this->workflowSteps = $this->selectedGroupe
                ? $this->selectedGroupe->workflowSteps()->orderBy('ordre')->get()
                : collect();
        } else {
            $this->selectedGroupe = WorkflowGroupe::where('model_type', 'prospect')
                ->where('actif', true)
                ->orderBy('ordre')
                ->first();
            
            if ($this->selectedGroupe) {
                $this->selectedWorkflowGroupeId = $this->selectedGroupe->id;
                $this->workflowSteps = $this->selectedGroupe->workflowSteps()->orderBy('ordre')->get();
            } else {
                $this->workflowSteps = collect();
            }
        }
    }

    public function selectWorkflowGroupe(int $groupeId): void
    {
        $this->selectedWorkflowGroupeId = $groupeId;
        $this->loadWorkflow();
    }

    public function toggleStepActif(int $stepId): void
    {
        $step = WorkflowStep::find($stepId);
        if ($step) {
            $step->update(['actif' => !$step->actif]);
            $this->loadWorkflow();
            Notification::make()
                ->title($step->actif ? 'Étape activée' : 'Étape désactivée')
                ->success()
                ->send();
        }
    }

    public function reorderSteps(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            WorkflowStep::where('id', $id)->update(['ordre' => $index]);
        }
        $this->loadWorkflow();
        Notification::make()
            ->title('Ordre mis à jour')
            ->success()
            ->send();
    }

    public function getWorkflowGroupesProperty(): Collection
    {
        return WorkflowGroupe::where('model_type', 'prospect')
            ->where('actif', true)
            ->orderBy('ordre')
            ->get();
    }

    public function getStepTypesProperty(): array
    {
        return WorkflowStep::TYPES;
    }

    public function getStepTypeColor(string $type): string
    {
        return match ($type) {
            'task' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'condition' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            'action' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'notification' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'approval' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
    }
}
