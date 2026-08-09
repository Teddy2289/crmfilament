<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvenementCalendrier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'titre',
        'description',
        'debut',
        'fin',
        'journee_entiere',
        'type',
        'statut',
        'lieu',
        'participants',
        'couleur',
        'user_id',
        'rendez_vous_id',
        'task_id',
    ];

    protected $casts = [
        'debut' => 'datetime',
        'fin' => 'datetime',
        'journee_entiere' => 'boolean',
        'participants' => 'array',
    ];

    const TYPES = [
        'rdv' => 'Rendez-vous',
        'tache' => 'Tâche',
        'rappel' => 'Rappel',
        'evenement' => 'Événement',
    ];

    const STATUTS = [
        'planifie' => 'Planifié',
        'en_cours' => 'En cours',
        'termine' => 'Terminé',
        'annule' => 'Annulé',
    ];

    const COULEURS = [
        'blue' => 'Bleu',
        'green' => 'Vert',
        'yellow' => 'Jaune',
        'red' => 'Rouge',
        'purple' => 'Violet',
        'pink' => 'Rose',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function rendezVous()
    {
        return $this->belongsTo(RendezVous::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePourUtilisateur($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeEntreDates($query, $debut, $fin)
    {
        return $query->where('debut', '>=', $debut)
            ->where('fin', '<=', $fin);
    }

    public function scopeDuJour($query)
    {
        return $query->whereDate('debut', now());
    }

    public function scopeDeLaSemaine($query)
    {
        return $query->whereBetween('debut', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeDuMois($query)
    {
        return $query->whereMonth('debut', now()->month)
            ->whereYear('debut', now()->year);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estEnCours(): bool
    {
        return now()->between($this->debut, $this->fin);
    }

    public function estPasse(): bool
    {
        return $this->fin < now();
    }

    public function estAVenir(): bool
    {
        return $this->debut > now();
    }

    public function duree(): int
    {
        return $this->debut->diffInMinutes($this->fin);
    }

    public function marquerTermine(): void
    {
        $this->update(['statut' => 'termine']);
    }

    public function annuler(): void
    {
        $this->update(['statut' => 'annule']);
    }
}
