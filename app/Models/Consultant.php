<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultant extends Model
{
    use SoftDeletes;

    /**
     * Statuts possibles selon le MEA.
     * ✅ Remplace l'ancien champ statut_vdi trop restrictif.
     */
    public const STATUTS = [
        'Mandataire' => 'Mandataire',
        'VDI' => 'VDI',
        'Salarié' => 'Salarié',
        'PRC' => 'PRC',
        'PIP' => 'PIP',
    ];

    protected $fillable = [
        'nom',
        'prenom',
        // ✅ Corrigé : statut_vdi → statut (Mandataire/VDI/Salarié/PRC/PIP)
        'statut',
        'departement',
        'entite_id',
    ];

    protected $casts = [
        'departement' => 'integer',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    // ── Relations ────────────────────────────────────────────────────

    public function entite()
    {
        return $this->belongsTo(EntiteCommerciale::class, 'entite_id');
    }

    public function campagnes()
    {
        return $this->hasMany(CampagnePhoning::class);
    }

    public function partenaires()
    {
        return $this->hasMany(Partenaire::class, 'conseiller_id');
    }

    public function activitesVente()
    {
        return $this->hasMany(ActiviteVente::class);
    }

    public function activitesPermanence()
    {
        return $this->hasMany(ActivitePermanence::class);
    }

    public function historiquesPartenaires()
    {
        return $this->hasMany(HistoriqueConseiller::class, 'ancien_conseiller_id');
    }

    public function dossierFormationsAccueil()
    {
        return $this->hasMany(DossierFormation::class, 'consultant_accueil_id');
    }

    public function dossierFormationsFormateur()
    {
        return $this->hasMany(DossierFormation::class, 'consultant_formateur_id');
    }
}
