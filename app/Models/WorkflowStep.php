<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    protected $fillable = [
        'workflow_id',
        'nom',
        'description',
        'ordre',
        'type_action',
        'assigne_a',
        'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
        'ordre' => 'integer',
    ];

    const TYPES_ACTION = [
        'approval' => 'Approbation',
        'notification' => 'Notification',
        'task' => 'Tâche',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function assigneA(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function getTypeActionLabelAttribute(): string
    {
        return self::TYPES_ACTION[$this->type_action] ?? $this->type_action;
    }
}
