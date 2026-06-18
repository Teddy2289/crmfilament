<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DossierFormation extends Model
{
    use SoftDeletes;

    protected $table = 'dossier_formations';

    protected $fillable = [
        'ref_client',
        'intitule_programme',
        'entite_id',
        'personne_id',
        'montant_ht',
        'montant_cpf',
        'date_vente',
        'statut_formation',
        'no_dossier_edof',
        'etat',
        'consultant_accueil_id',
        'consultant_formateur_id',
    ];

    protected $casts = [
        'montant_ht' => 'decimal:2',
        'montant_cpf' => 'decimal:2',
        'date_vente' => 'date',
    ];

    // ─── Relations ────────────────────────────────────────────────────

    public function entite()
    {
        return $this->belongsTo(EntiteCommerciale::class, 'entite_id');
    }

    public function personne()
    {
        return $this->belongsTo(Client::class, 'personne_id');
    }

    public function consultantAccueil()
    {
        return $this->belongsTo(Consultant::class, 'consultant_accueil_id');
    }

    public function consultantFormateur()
    {
        return $this->belongsTo(Consultant::class, 'consultant_formateur_id');
    }

    // ✅ CORRECTION : Utilisation de hasOne avec les bonnes clés
    public function heures()
    {
        return $this->hasOne(HeuresFormation::class, 'dossier_id', 'id');
    }

    public function planning()
    {
        return $this->hasOne(PlanningFormation::class, 'dossier_id', 'id');
    }

    // ─── Accesseurs pour les statuts ─────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        $mapping = [
            'a_venir' => 'À venir',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'valide' => 'Validé',
            'annule' => 'Annulé',
            'reporte' => 'Reporté',
        ];

        return $mapping[$this->statut_formation] ?? $this->statut_formation ?? '—';
    }

    public function getEtatLabelAttribute(): string
    {
        $mapping = [
            'brouillon' => 'Brouillon',
            'en_cours' => 'En cours',
            'soumis' => 'Soumis',
            'approuve' => 'Approuvé',
            'rejete' => 'Rejeté',
            'cloture' => 'Clôturé',
        ];

        return $mapping[$this->etat] ?? $this->etat ?? '—';
    }

    public function getStatutColorAttribute(): string
    {
        $mapping = [
            'a_venir' => 'gray',
            'en_cours' => 'warning',
            'termine' => 'success',
            'valide' => 'primary',
            'annule' => 'danger',
            'reporte' => 'info',
        ];

        return $mapping[$this->statut_formation] ?? 'gray';
    }

    public function getEtatColorAttribute(): string
    {
        $mapping = [
            'brouillon' => 'gray',
            'en_cours' => 'primary',
            'soumis' => 'warning',
            'approuve' => 'success',
            'rejete' => 'danger',
            'cloture' => 'success',
        ];

        return $mapping[$this->etat] ?? 'gray';
    }
}
