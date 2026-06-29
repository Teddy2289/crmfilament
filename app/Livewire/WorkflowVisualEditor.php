<?php

namespace App\Livewire;

use App\Models\WorkflowGroupe;
use App\Models\WorkflowStep;
use Livewire\Component;

class WorkflowVisualEditor extends Component
{
    public $workflowId;
    public $nodes = [];
    public $selectedNode = null;

    protected $listeners = ['workflow-saved' => 'saveWorkflow'];

    public function mount($workflowId = null)
    {
        $this->workflowId = $workflowId;
        $this->loadNodes();
    }

    public function loadNodes()
    {
        if ($this->workflowId) {
            $workflow = WorkflowGroupe::find($this->workflowId);
            if ($workflow) {
                $this->nodes = $workflow->workflowSteps->map(function ($step) {
                    return [
                        'id' => $step->id,
                        'label' => $step->label,
                        'type' => $step->type,
                        'ordre' => $step->ordre,
                        'code' => $step->code,
                        'config' => $step->config ?? [],
                        'x' => $step->config['x'] ?? 50 + ($step->ordre * 200),
                        'y' => $step->config['y'] ?? 50,
                        'parent_step_id' => $step->parent_step_id,
                        'condition_label' => $step->condition_label,
                    ];
                })->toArray();
            }
        }
    }

    public function saveWorkflow($data)
    {
        if (!$this->workflowId) {
            return;
        }

        $workflow = WorkflowGroupe::find($this->workflowId);
        if (!$workflow) {
            return;
        }

        // Mettre à jour les positions et ordres
        foreach ($data['nodes'] as $index => $nodeData) {
            $step = WorkflowStep::find($nodeData['id']);
            if ($step) {
                $step->update([
                    'ordre' => $index + 1,
                    'config' => array_merge($step->config ?? [], [
                        'x' => $nodeData['x'],
                        'y' => $nodeData['y'],
                    ]),
                ]);
            }
        }

        $this->dispatch('workflow-saved-successfully');
    }

    public function addNode($type, $config = [])
    {
        if (!$this->workflowId) {
            return;
        }

        $workflow = WorkflowGroupe::find($this->workflowId);
        if (!$workflow) {
            return;
        }

        $defaultConfig = [
            'x' => 50 + (count($this->nodes) * 200),
            'y' => 50,
        ];

        $config = array_merge($defaultConfig, $config);

        $newStep = WorkflowStep::create([
            'workflow_groupe_id' => $this->workflowId,
            'label' => $config['case_title'] ?? "Nouveau {$type}",
            'code' => strtolower($type) . '_' . time(),
            'type' => $type,
            'ordre' => count($this->nodes) + 1,
            'actif' => true,
            'config' => $config,
        ]);

        $this->loadNodes();
    }

    public function updateNodeConfig($nodeId, $config)
    {
        $step = WorkflowStep::find($nodeId);
        if ($step) {
            $currentConfig = $step->config ?? [];
            $step->update([
                'config' => array_merge($currentConfig, $config),
            ]);
            $this->loadNodes();
        }
    }

    public function deleteNode($nodeId)
    {
        $step = WorkflowStep::find($nodeId);
        if ($step) {
            $step->delete();
            $this->loadNodes();
        }
    }

    public function render()
    {
        return view('livewire.workflow-visual-editor');
    }
}
