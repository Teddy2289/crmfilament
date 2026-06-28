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
    ];

    protected $casts = [
        'config' => 'array',
        'actif' => 'boolean',
        'ordre' => 'integer',
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

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
