<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'type',
        'statut',
        'etapes',
        'created_by',
    ];

    protected $casts = [
        'etapes' => 'array',
    ];

    const TYPES = [
        'prospect' => 'Prospect',
        'partenaire' => 'Partenaire',
        'dossier_formation' => 'Dossier Formation',
        'custom' => 'Personnalisé',
    ];

    const STATUTS = [
        'draft' => 'Brouillon',
        'active' => 'Actif',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('ordre');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class);
    }

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'draft' => 'gray',
            'active' => 'success',
            'completed' => 'primary',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function startForEntity($entityId, $entityType): WorkflowApproval
    {
        $firstStep = $this->steps()->first();
        
        return $this->approvals()->create([
            'workflow_step_id' => $firstStep->id,
            'entity_id' => $entityId,
            'entity_type' => $entityType,
            'statut' => 'pending',
        ]);
    }
}
