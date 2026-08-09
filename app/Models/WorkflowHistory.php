<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkflowHistory extends Model
{
    protected $fillable = [
        'workflow_instance_id',
        'from_step_id',
        'to_step_id',
        'commentaire',
        'user_id',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function workflowInstance()
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function fromStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'from_step_id');
    }

    public function toStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'to_step_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
