<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApproval extends Model
{
    protected $fillable = [
        'workflow_id',
        'workflow_step_id',
        'entity_id',
        'entity_type',
        'approved_by',
        'statut',
        'commentaire',
        'approved_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    const STATUTS = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePending($query)
    {
        return $query->where('statut', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('statut', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('statut', 'rejected');
    }

    public function scopeForEntity($query, $entityId, $entityType)
    {
        return $query->where('entity_id', $entityId)->where('entity_type', $entityType);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function approve($userId, $commentaire = null): void
    {
        $this->update([
            'statut' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
            'commentaire' => $commentaire,
        ]);
    }

    public function reject($userId, $commentaire = null): void
    {
        $this->update([
            'statut' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'commentaire' => $commentaire,
        ]);
    }
}
