<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampagneMarketing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'type',
        'description',
        'date_debut',
        'date_fin',
        'statut',
        'cibles',
        'contenu',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'cibles' => 'array',
        'contenu' => 'array',
    ];

    const TYPES = [
        'email' => 'Email',
        'sms' => 'SMS',
        'newsletter' => 'Newsletter',
        'social' => 'Réseaux sociaux',
    ];

    const STATUTS = [
        'brouillon' => 'Brouillon',
        'active' => 'Active',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActives($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'active')
            ->where('date_debut', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_fin')
                    ->orWhere('date_fin', '>=', now());
            });
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function lancer(): void
    {
        $this->update([
            'statut' => 'active',
            'date_debut' => now(),
        ]);
    }

    public function terminer(): void
    {
        $this->update([
            'statut' => 'terminee',
            'date_fin' => now(),
        ]);
    }

    public function annuler(): void
    {
        $this->update([
            'statut' => 'annulee',
        ]);
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'active' &&
            $this->date_debut <= now() &&
            ($this->date_fin === null || $this->date_fin >= now());
    }
}
