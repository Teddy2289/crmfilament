<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $casts = [
        'date_echeance' => 'datetime',
        'date_realisation' => 'datetime',
    ];

    protected $fillable = [
        'titre',
        'description',
        'type',
        'statut',
        'date_echeance',
        'date_realisation',
        'assigne_a',
        'prospect_id',
        'partenaire_id',
        'created_by',
    ];

    const TYPES = [
        'tache' => 'Tâche',
        'rappel' => 'Rappel',
        'appel' => 'Appel',
        'rdv' => 'RDV',
    ];

    const STATUTS = [
        'a_faire' => 'À faire',
        'en_cours' => 'En cours',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
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
        return match ($this->statut) {
            'a_faire' => 'gray',
            'en_cours' => 'warning',
            'terminee' => 'success',
            'annulee' => 'danger',
            default => 'gray',
        };
    }

    public function getEstEnRetardAttribute(): bool
    {
        return $this->date_echeance && $this->date_echeance->isPast() && ! in_array($this->statut, ['terminee', 'annulee']);
    }

    public function getEstUrgentAttribute(): bool
    {
        return $this->date_echeance && $this->date_echeance->isToday() && ! in_array($this->statut, ['terminee', 'annulee']);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function marquerEnCours(): void
    {
        $this->update(['statut' => 'en_cours']);
    }

    public function marquerTerminee(): void
    {
        $this->update([
            'statut' => 'terminee',
            'date_realisation' => now(),
        ]);
    }

    public function annuler(?string $motif = null): void
    {
        $this->update([
            'statut' => 'annulee',
            'description' => $motif ? "Annulée : {$motif}" : $this->description,
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeAFaire($query)
    {
        return $query->where('statut', 'a_faire');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeTerminees($query)
    {
        return $query->where('statut', 'terminee');
    }

    public function scopeEnRetard($query)
    {
        return $query->where('date_echeance', '<', now())
            ->whereNotIn('statut', ['terminee', 'annulee']);
    }

    public function scopeUrgentes($query)
    {
        return $query->whereDate('date_echeance', today())
            ->whereNotIn('statut', ['terminee', 'annulee']);
    }

    public function scopeAssigneesA($query, int $userId)
    {
        return $query->where('assigne_a', $userId);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Relations ────────────────────────────────────────────────────
    public function assigneA()
    {
        return $this->belongsTo(User::class, 'assigne_a');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class);
    }

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }
}
