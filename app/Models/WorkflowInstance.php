<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'workflow_groupe_id',
        'current_step_id',
        'instanceable_type',
        'instanceable_id',
        'statut',
        'date_debut',
        'date_fin',
        'initiated_by',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
    ];

    const STATUTS = [
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'annule' => 'Annulé',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function workflowGroupe()
    {
        return $this->belongsTo(WorkflowGroupe::class);
    }

    public function currentStep()
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function instanceable()
    {
        return $this->morphTo();
    }

    public function initiatedBy()
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function histories()
    {
        return $this->hasMany(WorkflowHistory::class);
    }

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match ($this->statut) {
            'en_cours' => 'warning',
            'termine' => 'success',
            'annule' => 'danger',
            default => 'gray',
        };
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function avancerVers(WorkflowStep $step, ?string $commentaire = null): void
    {
        $fromStep = $this->currentStep;
        
        $this->update([
            'current_step_id' => $step->id,
        ]);

        WorkflowHistory::create([
            'workflow_instance_id' => $this->id,
            'from_step_id' => $fromStep?->id,
            'to_step_id' => $step->id,
            'commentaire' => $commentaire,
            'user_id' => auth()->id(),
        ]);

        // Si l'étape est finale, terminer le workflow
        if ($step->est_final ?? false) {
            $this->terminer();
        }
    }

    public function terminer(): void
    {
        $this->update([
            'statut' => 'termine',
            'date_fin' => now(),
        ]);
    }

    public function annuler(?string $motif = null): void
    {
        $this->update([
            'statut' => 'annule',
            'date_fin' => now(),
        ]);

        if ($motif) {
            WorkflowHistory::create([
                'workflow_instance_id' => $this->id,
                'commentaire' => "Annulé: {$motif}",
                'user_id' => auth()->id(),
            ]);
        }
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTermines($query)
    {
        return $query->where('statut', 'termine');
    }

    public function scopePourInstance($query, Model $instance)
    {
        return $query->where('instanceable_type', get_class($instance))
            ->where('instanceable_id', $instance->id);
    }

    public static function demarrerPour(Model $instance, WorkflowGroupe $workflowGroupe, ?int $userId = null): self
    {
        $firstStep = $workflowGroupe->workflowSteps()->where('ordre', 0)->first() 
            ?? $workflowGroupe->workflowSteps()->first();

        return self::create([
            'workflow_groupe_id' => $workflowGroupe->id,
            'current_step_id' => $firstStep?->id,
            'instanceable_type' => get_class($instance),
            'instanceable_id' => $instance->id,
            'statut' => 'en_cours',
            'date_debut' => now(),
            'initiated_by' => $userId ?? auth()->id(),
        ]);
    }
}
