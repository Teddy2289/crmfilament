<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'project_id',
        'date_prevue',
        'date_reelle',
        'statut',
        'progress',
        'assigned_to',
        'metadata',
    ];

    protected $casts = [
        'date_prevue' => 'date',
        'date_reelle' => 'date',
        'progress' => 'integer',
        'metadata' => 'array',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopePending($query)
    {
        return $query->where('statut', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('statut', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('statut', 'completed');
    }

    public function scopeDelayed($query)
    {
        return $query->where('statut', 'delayed');
    }

    public function scopeByProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date_prevue', '>=', now())->orderBy('date_prevue');
    }

    public function scopeOverdue($query)
    {
        return $query->where('date_prevue', '<', now())->where('statut', '!=', 'completed');
    }

    public function isOverdue(): bool
    {
        return $this->date_prevue < now() && !$this->isCompleted();
    }

    public function isCompleted(): bool
    {
        return $this->statut === 'completed';
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'statut' => 'completed',
            'date_reelle' => now(),
            'progress' => 100,
        ]);
    }

    public function markAsDelayed(): void
    {
        $this->update(['statut' => 'delayed']);
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'pending' => 'En attente',
            'in_progress' => 'En cours',
            'completed' => 'Terminé',
            'delayed' => 'Retardé',
            default => ucfirst($this->statut),
        };
    }

    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'pending' => 'gray',
            'in_progress' => 'primary',
            'completed' => 'success',
            'delayed' => 'danger',
            default => 'gray',
        };
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if ($this->isCompleted()) {
            return null;
        }
        return now()->diffInDays($this->date_prevue, false);
    }
}
