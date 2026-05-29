<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Proposition extends Model
{
    use SoftDeletes;

    protected $table = 'Propositions'; // ⚠️ Respect de la casse de la migration

    protected $casts = [
        'date_lancement' => 'date',
        'date_vente' => 'date',
        'date_debut_formation' => 'date',
        'date_fin_formation' => 'date',
        'date_certification' => 'date',
        'nb_heures_formation' => 'integer',
        'heures_realisees' => 'integer',
        'heures_restantes' => 'integer',
        'extra_data' => 'array',
    ];

    protected $fillable = [
        'source_sheet',
        'ref_client',
        'tiers',
        'etat',
        'date_lancement',
        'date_vente',
        'nb_heures_formation',
        'heures_realisees',
        'heures_restantes',
        'date_debut_formation',
        'date_fin_formation',
        'consultant_formateur',
        'date_certification',
        'extra_data',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getProgressionAttribute(): float
    {
        if (!$this->nb_heures_formation || $this->nb_heures_formation === 0) {
            return 0;
        }

        return round(($this->heures_realisees / $this->nb_heures_formation) * 100, 1);
    }

    public function getProgressionRestanteAttribute(): float
    {
        return 100 - $this->progression;
    }

    public function getDureeFormationJoursAttribute(): ?int
    {
        if (!$this->date_debut_formation || !$this->date_fin_formation) {
            return null;
        }

        return $this->date_debut_formation->diffInDays($this->date_fin_formation);
    }

    public function getDureeDepuisLancementJoursAttribute(): ?int
    {
        if (!$this->date_lancement) {
            return null;
        }

        return $this->date_lancement->diffInDays(now());
    }

    public function getEstTermineeAttribute(): bool
    {
        return $this->etat === 'Terminée';
    }

    public function getEstEnCoursAttribute(): bool
    {
        return in_array($this->etat, ['En cours', 'Lancée', 'Active']);
    }

    public function getEstAnnuleeAttribute(): bool
    {
        return $this->etat === 'Annulée';
    }

    public function getEstEnRetardAttribute(): bool
    {
        if (!$this->date_fin_formation || $this->estTerminee || $this->estAnnulee) {
            return false;
        }

        return now()->gt($this->date_fin_formation) && $this->heures_restantes > 0;
    }

    public function getTauxRemplissageAttribute(): float
    {
        if (!$this->nb_heures_formation || $this->nb_heures_formation === 0) {
            return 0;
        }

        $total = $this->nb_heures_formation;
        $planifiees = $this->heures_realisees + $this->heures_restantes;

        return round(($planifiees / $total) * 100, 1);
    }

    public function getStatutFormationAttribute(): string
    {
        if ($this->estTerminee) return 'Terminée';
        if ($this->estAnnulee) return 'Annulée';
        if ($this->estEnRetard) return 'En retard';
        if ($this->progression > 0) return 'En cours';
        if ($this->date_debut_formation && now()->lt($this->date_debut_formation)) {
            return 'Planifiée';
        }
        return 'En attente';
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estComplete(): bool
    {
        return $this->heures_restantes === 0 && $this->heures_realisees > 0;
    }

    public function mettreAJourHeures(int $realisees, ?int $restantes = null): void
    {
        $data = ['heures_realisees' => $realisees];

        if ($restantes !== null) {
            $data['heures_restantes'] = $restantes;
        } elseif ($this->nb_heures_formation) {
            $data['heures_restantes'] = max(0, $this->nb_heures_formation - $realisees);
        }

        $this->update($data);
    }

    public function ajouterHeuresRealisees(int $heures): void
    {
        $this->mettreAJourHeures(
            $this->heures_realisees + $heures,
            max(0, $this->heures_restantes - $heures)
        );
    }

    public function terminer(): void
    {
        $this->update([
            'etat' => 'Terminée',
            'heures_realisees' => $this->nb_heures_formation,
            'heures_restantes' => 0,
            'date_fin_formation' => $this->date_fin_formation ?? now(),
        ]);
    }

    public function annuler(string $motif = null): void
    {
        $this->update([
            'etat' => 'Annulée',
            'extra_data' => array_merge($this->extra_data ?? [], [
                'motif_annulation' => $motif,
                'date_annulation' => now()->toDateString(),
            ]),
        ]);
    }

    public function certifier(\DateTime $date = null): void
    {
        $this->update([
            'date_certification' => $date ?? now(),
        ]);
    }

    public function assignerFormateur(string $formateur): void
    {
        $this->update(['consultant_formateur' => $formateur]);
    }

    public function programmerFormation(\DateTime $debut, \DateTime $fin): void
    {
        $this->update([
            'date_debut_formation' => $debut,
            'date_fin_formation' => $fin,
            'etat' => 'Planifiée',
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActives($query): Builder
    {
        return $query->whereNotIn('etat', ['Terminée', 'Annulée']);
    }

    public function scopeTerminees($query): Builder
    {
        return $query->where('etat', 'Terminée');
    }

    public function scopeAnnulees($query): Builder
    {
        return $query->where('etat', 'Annulée');
    }

    public function scopeEnCours($query): Builder
    {
        return $query->where('etat', 'En cours');
    }

    public function scopeEnRetard($query): Builder
    {
        return $query->whereNotIn('etat', ['Terminée', 'Annulée'])
                     ->where('date_fin_formation', '<', now())
                     ->where('heures_restantes', '>', 0);
    }

    public function scopeParFormateur($query, string $formateur): Builder
    {
        return $query->where('consultant_formateur', $formateur);
    }

    public function scopeParEtat($query, string $etat): Builder
    {
        return $query->where('etat', $etat);
    }

    public function scopeAVenir($query): Builder
    {
        return $query->where('date_debut_formation', '>', now());
    }

    public function scopeSansFormateur($query): Builder
    {
        return $query->whereNull('consultant_formateur');
    }

    public function scopeNonCertifiees($query): Builder
    {
        return $query->whereNull('date_certification')
                     ->where('etat', 'Terminée');
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('date_lancement', now()->month)
                     ->whereYear('date_lancement', now()->year);
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(): array
    {
        return [
            'total' => static::count(),
            'actives' => static::actives()->count(),
            'terminees' => static::terminees()->count(),
            'annulees' => static::annulees()->count(),
            'en_retard' => static::enRetard()->count(),
            'heures_formees' => static::sum('heures_realisees'),
            'heures_restantes' => static::actives()->sum('heures_restantes'),
            'taux_completion' => static::getTauxCompletion(),
            'par_formateur' => static::getRepartitionParFormateur(),
            'par_etat' => static::getRepartitionParEtat(),
        ];
    }

    public static function getTauxCompletion(): float
    {
        $total = static::count();
        if ($total === 0) return 0;

        return round((static::terminees()->count() / $total) * 100, 1);
    }

    public static function getRepartitionParFormateur(): array
    {
        return static::selectRaw('consultant_formateur, COUNT(*) as total')
            ->whereNotNull('consultant_formateur')
            ->groupBy('consultant_formateur')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'consultant_formateur')
            ->toArray();
    }

    public static function getRepartitionParEtat(): array
    {
        return static::selectRaw('etat, COUNT(*) as total')
            ->whereNotNull('etat')
            ->groupBy('etat')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'etat')
            ->toArray();
    }

    // ── Relations ────────────────────────────────────────────────────
    public function client()
    {
        return $this->belongsTo(Client::class, 'ref_client', 'ref_client');
    }
}
