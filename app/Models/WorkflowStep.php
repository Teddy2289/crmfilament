<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_groupe_id',
        'label',
        'code',
        'type',
        'ordre',
        'config',
        'actif',
        'parent_step_id', // Pour les branches
        'condition_label', // Label de la condition (ex: "Oui", "Non")
        'est_final', // Si c'est l'étape finale
    ];

    protected $casts = [
        'config' => 'array',
        'actif' => 'boolean',
        'ordre' => 'integer',
        'est_final' => 'boolean',
    ];

    const TYPES = [
        'task' => 'Tâche',
        'condition' => 'Condition',
        'action' => 'Action',
        'notification' => 'Notification',
        'approval' => 'Validation',
    ];

    public function workflowGroupe(): BelongsTo
    {
        return $this->belongsTo(WorkflowGroupe::class);
    }

    public function parentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'parent_step_id');
    }

    public function childSteps()
    {
        return $this->hasMany(WorkflowStep::class, 'parent_step_id');
    }

    public function instances()
    {
        return $this->hasMany(WorkflowInstance::class, 'current_step_id');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
