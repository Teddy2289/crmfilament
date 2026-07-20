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

    protected static function booted(): void
    {
        static::saved(function (DossierFormation $dossier) {
            if ($dossier->wasChanged('personne_id')) {
                static::actualiserActiviteVentePourPersonne($dossier->getOriginal('personne_id'));
            }

            static::actualiserActiviteVentePourPersonne($dossier->personne_id);
        });

        static::deleted(function (DossierFormation $dossier) {
            static::actualiserActiviteVentePourPersonne($dossier->personne_id);
        });

        static::restored(function (DossierFormation $dossier) {
            static::actualiserActiviteVentePourPersonne($dossier->personne_id);
        });
    }

    private static function actualiserActiviteVentePourPersonne(?int $personneId): void
    {
        if (! $personneId) {
            return;
        }

        $partenaireId = Client::withTrashed()
            ->whereKey($personneId)
            ->value('partenaire_id');

        ActiviteVente::actualiserPourPartenaire($partenaireId);
    }

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

    // ─── Statuts : source unique (options de formulaire, table, filtres,
    // fiche) — les valeurs 'interrompu'/'abandon'/'termine' (etat) sont
    // absentes du mapping historique alors qu'elles existent réellement
    // en base (cf. audit tinker du 2026-07-20).
    // ────────────────────────────────────────────────────────────────

    public static function statutFormationOptions(): array
    {
        return [
            'a_venir' => 'À venir',
            'en_cours' => 'En cours',
            'termine' => 'Terminé',
            'valide' => 'Validé',
            'reporte' => 'Reporté',
            'interrompu' => 'Interrompu',
            'annule' => 'Annulé',
            'abandon' => 'Abandonné',
        ];
    }

    public static function statutFormationLabel(?string $statut): string
    {
        return static::statutFormationOptions()[$statut] ?? $statut ?? '—';
    }

    public static function statutFormationColor(?string $statut): string
    {
        return match ($statut) {
            'a_venir' => 'blue',
            'en_cours' => 'amber',
            'termine' => 'green',
            'valide' => 'indigo',
            'reporte' => 'gray',
            'interrompu' => 'orange',
            'annule' => 'red',
            'abandon' => 'pink',
            default => 'gray',
        };
    }

    public static function etatOptions(): array
    {
        return [
            'brouillon' => 'Brouillon',
            'en_cours' => 'En cours',
            'soumis' => 'Soumis',
            'approuve' => 'Approuvé',
            'rejete' => 'Rejeté',
            'cloture' => 'Clôturé',
            'termine' => 'Terminé',
        ];
    }

    public static function etatLabel(?string $etat): string
    {
        return static::etatOptions()[$etat] ?? $etat ?? '—';
    }

    public static function etatColor(?string $etat): string
    {
        return match ($etat) {
            'brouillon' => 'gray',
            'en_cours' => 'primary',
            'soumis' => 'warning',
            'approuve' => 'success',
            'rejete' => 'danger',
            'cloture' => 'indigo',
            'termine' => 'teal',
            default => 'gray',
        };
    }

    // ─── Accesseurs pour les statuts (fiche/exports) ──────────────────

    public function getStatutLabelAttribute(): string
    {
        return static::statutFormationLabel($this->statut_formation);
    }

    public function getEtatLabelAttribute(): string
    {
        return static::etatLabel($this->etat);
    }

    public function getStatutColorAttribute(): string
    {
        return static::statutFormationColor($this->statut_formation);
    }

    public function getEtatColorAttribute(): string
    {
        return static::etatColor($this->etat);
    }
}
