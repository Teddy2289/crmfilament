<?php

namespace App\Models;

use App\Enums\ModePaiement;
use App\Enums\StatutPaiement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Facture extends Model
{
    use SoftDeletes;

    protected $table = 'factures';

    protected $casts = [
        'statut_paiement' => StatutPaiement::class,
        'mode_paiement' => ModePaiement::class,
        'lignes' => 'array',
        'date_emission' => 'date',
        'date_echeance' => 'date',
        'date_paiement_effectif' => 'date',
        'total_ht' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
        'acompte_deja_verse' => 'decimal:2',
        'solde_restant_du' => 'decimal:2',
        'penalites_retard' => 'decimal:2',
    ];

    protected $fillable = [
        'numero',
        'bon_de_commande_id',
        'ticket_id',
        'artisan_id',
        'contact_particulier_id',
        'lignes',
        'total_ht',
        'montant_tva',
        'total_ttc',
        'acompte_deja_verse',
        'solde_restant_du',
        'date_echeance',
        'mode_paiement',
        'statut_paiement',
        'date_paiement_effectif',
        'penalites_retard',
        'avoir_id',
        'fichier_pdf',
        'conditions_paiement',
        'notes',
    ];

    const TAUX_PENALITES_RETARD = 0.10;

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getStatutPaiementLabelAttribute(): string
    {
        return $this->statut_paiement->label();
    }

    public function getStatutPaiementColorAttribute(): string
    {
        return $this->statut_paiement->color();
    }

    public function getStatutPaiementIconAttribute(): string
    {
        return $this->statut_paiement->icon();
    }

    public function getModePaiementLabelAttribute(): string
    {
        return $this->mode_paiement?->label() ?? 'Non renseigné';
    }

    public function getEstPayeeAttribute(): bool
    {
        return $this->statut_paiement === StatutPaiement::Paye;
    }

    public function getEstEnRetardAttribute(): bool
    {
        return $this->statut_paiement === StatutPaiement::EnRetard ||
            ($this->statut_paiement === StatutPaiement::EnAttente && $this->date_echeance->isPast());
    }

    public function getEstLitigieuxAttribute(): bool
    {
        return $this->statut_paiement === StatutPaiement::Litigieux;
    }

    public function getJoursRetardAttribute(): int
    {
        if (! $this->date_echeance->isPast() || $this->est_payee) {
            return 0;
        }

        return now()->diffInDays($this->date_echeance);
    }

    public function getUrlPdfAttribute(): ?string
    {
        if (! $this->fichier_pdf) {
            return null;
        }

        return Storage::url($this->fichier_pdf);
    }

    public function getAvoirAssocieAttribute(): bool
    {
        return ! is_null($this->avoir_id);
    }

    // ── Calculs financiers (CORRIGÉS) ───────────────────────────────

    /**
     * Calcule totaux HT, TVA, TTC et solde sans forcer de requêtes SQL intermédiaires.
     */
    public function recalculerTotaux(): void
    {
        $totalHt = 0.0;
        $totalTva = 0.0;

        foreach ($this->lignes ?? [] as $ligne) {
            $ht = ($ligne['quantite'] ?? 1) * ($ligne['prix_unitaire_ht'] ?? 0);
            $totalHt += $ht;
            $totalTva += $ht * (($ligne['taux_tva'] ?? 20) / 100);
        }

        $totalTtc = $totalHt + $totalTva;
        $acompte = $this->acompte_deja_verse ?? 0;
        $soldeRestantDu = max(0, $totalTtc - $acompte);

        // Correction : Modification directe des propriétés
        $this->total_ht = round($totalHt, 2);
        $this->montant_tva = round($totalTva, 2);
        $this->total_ttc = round($totalTtc, 2);
        $this->solde_restant_du = round($soldeRestantDu, 2);
    }

    /**
     * Calcule les pénalités sans appeler ->update()
     */
    public function calculerPenalites(): void
    {
        $joursRetard = $this->jours_retard;
        if ($joursRetard <= 0) {
            return;
        }

        $penalites = $this->solde_restant_du
            * self::TAUX_PENALITES_RETARD
            * ($joursRetard / 365);

        // Correction : Modification directe des propriétés
        $this->penalites_retard = round($penalites, 2);
        $this->statut_paiement = StatutPaiement::EnRetard;
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function enregistrerPaiement(float $montant, ModePaiement $mode, ?\DateTime $datePaiement = null): void
    {
        $solde = $this->solde_restant_du;
        $date = $datePaiement ?? now();
        $estTotal = abs($montant - $solde) < 0.01;

        $this->update([
            'mode_paiement' => $mode,
            'date_paiement_effectif' => $estTotal ? $date : $this->date_paiement_effectif,
            'statut_paiement' => $estTotal ? StatutPaiement::Paye : StatutPaiement::Partiel,
            'solde_restant_du' => $estTotal ? 0 : round(max(0, $solde - $montant), 2),
        ]);
    }

    public function marquerLitigieux(?string $motif = null): void
    {
        $this->update([
            'statut_paiement' => StatutPaiement::Litigieux,
            'notes' => $motif
                ? ($this->notes ? $this->notes."\n[Litige] {$motif}" : "[Litige] {$motif}")
                : $this->notes,
        ]);
    }

    public function associerAvoir(Facture $avoir): void
    {
        if ($avoir->id === $this->id) {
            throw new \Exception('Une facture ne peut pas être son propre avoir.');
        }
        $this->update(['avoir_id' => $avoir->id]);
    }

    public function doitEtreRelancee(): bool
    {
        return $this->est_en_retard && ! $this->est_payee && ! $this->est_litigieux;
    }

    public static function genererNumero(): string
    {
        $annee = now()->year;
        $dernierN = static::whereYear('created_at', $annee)->count() + 1;

        return 'FAC-'.$annee.'-'.str_pad($dernierN, 4, '0', STR_PAD_LEFT);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeEnAttente($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::EnAttente);
    }

    public function scopePartielles($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::Partiel);
    }

    public function scopePayees($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::Paye);
    }

    public function scopeEnRetard($query): Builder
    {
        return $query->where(function ($q) {
            $q->where('statut_paiement', StatutPaiement::EnRetard)
                ->orWhere(function ($q2) {
                    $q2->where('statut_paiement', StatutPaiement::EnAttente)
                        ->where('date_echeance', '<', now());
                });
        });
    }

    public function scopeLitigieuses($query): Builder
    {
        return $query->where('statut_paiement', StatutPaiement::Litigieux);
    }

    public function scopeNonPayees($query): Builder
    {
        return $query->whereNotIn('statut_paiement', [StatutPaiement::Paye->value]);
    }

    public function scopeARelancer($query): Builder
    {
        return $query->enRetard()->where('statut_paiement', '!=', StatutPaiement::Litigieux->value);
    }

    public function scopeParArtisan($query, int $artisanId): Builder
    {
        return $query->where('artisan_id', $artisanId);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
    }

    public function scopeSansPdf($query): Builder
    {
        return $query->whereNull('fichier_pdf');
    }

    // ── KPIs ────────────────────────────────────────────────────────

    public static function getKpis(): array
    {
        return [
            'total_emises_mois' => static::duMois()->count(),
            'en_attente_paiement' => static::nonPayees()->count(),
            'en_retard' => static::enRetard()->count(),
            'litigieuses' => static::litigieuses()->count(),
            'a_relancer' => static::aRelancer()->count(),
            'ca_encaisse_mois' => static::payees()->duMois()->sum('total_ttc'),
            'encours_total' => static::nonPayees()->sum('solde_restant_du'),
            'taux_recouvrement' => static::getTauxRecouvrement(),
            'delai_moyen_paiement' => static::getDelaiMoyenPaiement(),
        ];
    }

    public static function getTauxRecouvrement(): float
    {
        $total = static::count();

        return $total === 0 ? 0 : round((static::payees()->count() / $total) * 100, 1);
    }

    public static function getDelaiMoyenPaiement(): float
    {
        return round(static::payees()->whereNotNull('date_paiement_effectif')->get()->avg(fn ($f) => $f->created_at->diffInDays($f->date_paiement_effectif)) ?? 0, 1);
    }

    // ── Boot (CORRIGÉ) ──────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Facture $facture) {
            if (empty($facture->numero)) {
                $facture->numero = static::genererNumero();
            }
            if (empty($facture->statut_paiement)) {
                $facture->statut_paiement = StatutPaiement::EnAttente;
            }
            if (empty($facture->date_emission)) {
                $facture->date_emission = now();
            }
            if (empty($facture->date_echeance)) {
                $facture->date_echeance = now()->addDays(30);
            }
        });

        // Utilisation de SAVING pour centraliser les calculs proprement avant l'écriture en BDD
        static::saving(function (Facture $facture) {
            // On calcule si création OU si les données financières ont changé
            if (! $facture->exists || $facture->isDirty('lignes') || $facture->isDirty('acompte_deja_verse')) {
                $facture->recalculerTotaux();
            }

            // Vérification et calcul automatique du retard avant la sauvegarde
            if ($facture->statut_paiement === StatutPaiement::EnAttente && $facture->date_echeance?->isPast()) {
                $facture->calculerPenalites();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function bonDeCommande()
    {
        return $this->belongsTo(BonDeCommande::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function artisan()
    {
        return $this->belongsTo(Artisan::class);
    }

    public function contactParticulier()
    {
        return $this->belongsTo(ContactParticulier::class);
    }

    public function avoir()
    {
        return $this->belongsTo(Facture::class, 'avoir_id');
    }

    public function factureOrigine()
    {
        return $this->hasOne(Facture::class, 'avoir_id');
    }
}
