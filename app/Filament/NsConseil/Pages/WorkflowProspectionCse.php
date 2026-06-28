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

    // ── Branch color helpers for logigramme style ─────────────────────────────
    public function getBranchColor(string $branch): string
    {
        return match ($branch) {
            'yes', 'success', 'joint' => 'border-green-300 bg-green-50 dark:border-green-700 dark:bg-green-900/20',
            'no', 'failure', 'non_abouti' => 'border-red-300 bg-red-50 dark:border-red-700 dark:bg-red-900/20',
            'retry', 'recall' => 'border-yellow-300 bg-yellow-50 dark:border-yellow-700 dark:bg-yellow-900/20',
            'info', 'neutral' => 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-900/20',
            default => 'border-gray-300 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/20',
        };
    }

    public function getBranchTextColor(string $branch): string
    {
        return match ($branch) {
            'yes', 'success', 'joint' => 'text-green-700 dark:text-green-300',
            'no', 'failure', 'non_abouti' => 'text-red-700 dark:text-red-300',
            'retry', 'recall' => 'text-yellow-700 dark:text-yellow-300',
            'info', 'neutral' => 'text-blue-700 dark:text-blue-300',
            default => 'text-gray-700 dark:text-gray-300',
        };
    }

    public function getBranchLabel(string $branch): string
    {
        return match ($branch) {
            'yes' => 'OUI',
            'no' => 'NON',
            'success' => 'SUCCÈS',
            'failure' => 'ÉCHEC',
            'joint' => 'JOINT',
            'non_abouti' => 'NON ABOUTI',
            'retry' => 'RÉESSAYER',
            'recall' => 'RAPPEL',
            'info' => 'INFO',
            'neutral' => 'NEUTRE',
            default => strtoupper($branch),
        };
    }

    public function getBranchContentColor(string $branch): string
    {
        return match ($branch) {
            'yes', 'success', 'joint' => 'text-green-900 dark:text-green-100',
            'no', 'failure', 'non_abouti' => 'text-red-900 dark:text-red-100',
            'retry', 'recall' => 'text-yellow-900 dark:text-yellow-100',
            'info', 'neutral' => 'text-blue-900 dark:text-blue-100',
            default => 'text-gray-900 dark:text-gray-100',
        };
    }

    public function getTagColor(string $tag): string
    {
        return match ($tag) {
            'STD_NR' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            'CSE_NR' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
            'RP' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            'RPC' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            'KO' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            'QF' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
        };
    }
}
