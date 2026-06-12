<?php

namespace App\Models;

use App\Enums\StatutAffaireIntervention;
use App\Enums\TicketStatut;
use App\Enums\CanalContactPreferentiel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AffaireIntervention extends Model
{
    use SoftDeletes;

    protected $table = 'affaire_interventions';

    protected $casts = [
        'statut'                     => StatutAffaireIntervention::class,
        'date_rdv_prevue'            => 'datetime',
        'date_notification_artisan'  => 'datetime',
        'date_confirmation_artisan'  => 'datetime',
        'date_debut_reelle'          => 'datetime',
        'date_fin_reelle'            => 'datetime',
        'date_signature_client'      => 'datetime',
        'signature_client'           => 'boolean',
        'delai_confirmation_minutes' => 'integer',
        'duree_reelle_minutes'       => 'integer',
        'satisfaction_immediate'     => 'integer',
        'numero_tentative'           => 'integer',
    ];

    protected $fillable = [
        'reference',
        'ticket_id',
        'artisan_id',
        'operateur_dispatch_id',
        'statut',
        'numero_tentative',
        'motif_annulation',
        // Planning
        'date_rdv_prevue',
        'creneau_debut',
        'creneau_fin',
        'date_notification_artisan',
        'date_confirmation_artisan',
        'delai_confirmation_minutes',
        'canal_notification',
        // Réalisation
        'date_debut_reelle',
        'date_fin_reelle',
        'duree_reelle_minutes',
        'description_travaux_realises',
        'compte_rendu_artisan',
        // Validation client
        'signature_client',
        'date_signature_client',
        'satisfaction_immediate',
        // Notes
        'notes_dispatch',
        'notes_intervention',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return $this->statut?->label() ?? 'Non défini';
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut?->color() ?? 'gray';
    }

    public function getStatutIconAttribute(): string
    {
        return $this->statut?->icon() ?? 'heroicon-o-question-mark-circle';
    }

    public function getDelaiConfirmationFormateAttribute(): string
    {
        if (!$this->delai_confirmation_minutes) {
            return 'N/A';
        }

        $min = $this->delai_confirmation_minutes;
        return $min < 60 ? "{$min} min" : floor($min / 60) . 'h' . ($min % 60 ? ' ' . ($min % 60) . 'min' : '');
    }

    public function getDureeReelleFormateeAttribute(): string
    {
        if (!$this->duree_reelle_minutes) {
            return 'N/A';
        }

        $min = $this->duree_reelle_minutes;
        return $min < 60 ? "{$min} min" : floor($min / 60) . 'h' . ($min % 60 ? ' ' . ($min % 60) . 'min' : '');
    }

    public function getSlaRespecteeAttribute(): bool
    {
        if (!$this->date_notification_artisan || !$this->date_confirmation_artisan) {
            return true;
        }

        $ticket = $this->ticket;
        if (!$ticket?->niveau_priorite) {
            return true;
        }

        return $this->delai_confirmation_minutes <= $ticket->niveau_priorite->delaiMaxMinutes();
    }

    public function getEstEnRetardAttribute(): bool
    {
        if (!$this->statut?->estActive() || !$this->date_rdv_prevue) {
            return false;
        }

        return $this->date_rdv_prevue->isPast() && $this->statut === StatutAffaireIntervention::Confirmee;
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function peutPasserA(StatutAffaireIntervention $nouveauStatut): bool
    {
        return in_array($nouveauStatut, $this->statut->statutsSuivants());
    }

    public function changerStatut(StatutAffaireIntervention $nouveauStatut, ?string $notes = null): void
    {
        if (!$this->peutPasserA($nouveauStatut)) {
            throw new \Exception(
                "Transition impossible : {$this->statut->value} → {$nouveauStatut->value}"
            );
        }

        $data = ['statut' => $nouveauStatut];

        if ($notes) {
            $data['notes_intervention'] = $this->notes_intervention
                ? $this->notes_intervention . "\n[" . now()->format('d/m/Y H:i') . "] {$notes}"
                : $notes;
        }

        $this->update($data);
    }

    /**
     * L'artisan confirme sa venue (P4).
     * Met à jour le ticket en ArtisanConfirme.
     */
    public function confirmerParArtisan(): void
    {
        $this->changerStatut(StatutAffaireIntervention::Confirmee);

        $this->update([
            'date_confirmation_artisan'  => now(),
            'delai_confirmation_minutes' => $this->date_notification_artisan
                ? (int) $this->date_notification_artisan->diffInMinutes(now())
                : null,
        ]);

        $this->ticket?->changerStatut(
            TicketStatut::ArtisanConfirme,
            "Artisan {$this->artisan?->nom_complet} a confirmé — Affaire #{$this->reference}"
        );
    }

    /**
     * L'artisan démarre l'intervention sur place.
     */
    public function demarrer(): void
    {
        $this->changerStatut(StatutAffaireIntervention::EnCours);
        $this->update(['date_debut_reelle' => now()]);
    }

    /**
     * L'artisan clôture l'intervention avec son compte-rendu.
     */
    public function finaliserParArtisan(string $compteRendu, ?string $descriptionTravaux = null): void
    {
        $this->changerStatut(StatutAffaireIntervention::Realisee);

        $data = [
            'date_fin_reelle'             => now(),
            'compte_rendu_artisan'        => $compteRendu,
            'description_travaux_realises' => $descriptionTravaux,
        ];

        if ($this->date_debut_reelle) {
            $data['duree_reelle_minutes'] = (int) $this->date_debut_reelle->diffInMinutes(now());
        }

        $this->update($data);

        $this->ticket?->changerStatut(
            TicketStatut::InterventionRealisee,
            "Intervention terminée — Affaire #{$this->reference}"
        );
    }

    /**
     * Le client valide et signe le bon d'intervention.
     */
    public function validerParClient(int $satisfactionImmédiate = null): void
    {
        $this->changerStatut(StatutAffaireIntervention::ValideeClient);

        $this->update([
            'signature_client'       => true,
            'date_signature_client'  => now(),
            'satisfaction_immediate' => $satisfactionImmédiate,
        ]);
    }

    /**
     * Annulation de l'affaire (artisan refuse ou client annule).
     * Crée automatiquement une nouvelle tentative de dispatch sur le ticket.
     */
    public function annuler(string $motif): void
    {
        $this->changerStatut(StatutAffaireIntervention::Annulee, $motif);
        $this->update(['motif_annulation' => $motif]);

        // Rebascule le ticket en attente de re-dispatch
        $this->ticket?->changerStatut(
            TicketStatut::FicheComplete,
            "Affaire #{$this->reference} annulée : {$motif}"
        );
    }

    /**
     * Déclare un échec (artisan absent, intervention impossible).
     */
    public function declarerEchec(string $motif): void
    {
        $this->changerStatut(StatutAffaireIntervention::Echec, $motif);
        $this->update(['motif_annulation' => $motif]);

        $this->ticket?->changerStatut(
            TicketStatut::FicheComplete,
            "Échec intervention #{$this->reference} : {$motif}"
        );
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeActives($query): Builder
    {
        return $query->whereIn('statut', [
            StatutAffaireIntervention::EnAttente->value,
            StatutAffaireIntervention::Confirmee->value,
            StatutAffaireIntervention::EnCours->value,
        ]);
    }

    public function scopeEnAttente($query): Builder
    {
        return $query->where('statut', StatutAffaireIntervention::EnAttente->value);
    }

    public function scopeConfirmees($query): Builder
    {
        return $query->where('statut', StatutAffaireIntervention::Confirmee->value);
    }

    public function scopeEnCours($query): Builder
    {
        return $query->where('statut', StatutAffaireIntervention::EnCours->value);
    }

    public function scopeRealisees($query): Builder
    {
        return $query->where('statut', StatutAffaireIntervention::Realisee->value);
    }

    public function scopeEnEchec($query): Builder
    {
        return $query->whereIn('statut', [
            StatutAffaireIntervention::Annulee->value,
            StatutAffaireIntervention::Echec->value,
        ]);
    }

    public function scopeEnRetardConfirmation($query): Builder
    {
        return $query->where('statut', StatutAffaireIntervention::EnAttente->value)
            ->where('date_notification_artisan', '<', now()->subMinutes(30));
    }

    public function scopeDuJour($query): Builder
    {
        return $query->whereDate('date_rdv_prevue', today());
    }

    public function scopeByArtisan($query, int $artisanId): Builder
    {
        return $query->where('artisan_id', $artisanId);
    }

    // ── KPIs ────────────────────────────────────────────────────────

    public static function getKpis(): array
    {
        return [
            'en_attente'           => static::enAttente()->count(),
            'confirmees'           => static::confirmees()->count(),
            'en_cours'             => static::enCours()->count(),
            'realisees_jour'       => static::realisees()->duJour()->count(),
            'en_retard'            => static::enRetardConfirmation()->count(),
            'delai_moyen_confirmation' => static::getDelaiMoyenConfirmation(),
            'taux_reussite'        => static::getTauxReussite(),
        ];
    }

    public static function getDelaiMoyenConfirmation(): float
    {
        return round(
            static::whereNotNull('delai_confirmation_minutes')->avg('delai_confirmation_minutes') ?? 0,
            1
        );
    }

    public static function getTauxReussite(): float
    {
        $total = static::whereIn('statut', [
            StatutAffaireIntervention::Realisee->value,
            StatutAffaireIntervention::ValideeClient->value,
            StatutAffaireIntervention::Annulee->value,
            StatutAffaireIntervention::Echec->value,
        ])->count();

        if ($total === 0) return 100;

        $reussies = static::whereIn('statut', [
            StatutAffaireIntervention::Realisee->value,
            StatutAffaireIntervention::ValideeClient->value,
        ])->count();

        return round(($reussies / $total) * 100, 1);
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (AffaireIntervention $affaire) {
            // Don't generate reference here if it's already set
            if (empty($affaire->reference)) {
                $affaire->reference = static::generateUniqueReference();
            }

            if (empty($affaire->statut)) {
                $affaire->statut = StatutAffaireIntervention::EnAttente;
            }

            if (empty($affaire->date_notification_artisan)) {
                $affaire->date_notification_artisan = now();
            }

            // Numéro de tentative : compte les affaires précédentes sur ce ticket
            if (empty($affaire->numero_tentative) && $affaire->ticket_id) {
                $affaire->numero_tentative = static::where('ticket_id', $affaire->ticket_id)->count() + 1;
            }
        });
    }

    public static function generateUniqueReference(): string
    {
        $year = now()->year;
        $prefix = 'AFF';

        // Get the last reference for this year using a more reliable method
        $lastReference = self::query()
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->value('reference');

        if ($lastReference && preg_match('/AFF-' . $year . '-(\d+)/', $lastReference, $matches)) {
            $lastNumber = (int) $matches[1];
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . '-' . $year . '-' . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }



    public static function genererReference(): string
    {
        // Solution: utiliser DB facade au lieu de self::query()
        $count = \DB::table('affaire_interventions')
            ->whereYear('created_at', now()->year)
            ->count() + 1;

        return 'AFF-' . now()->year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }



    // ── Relations ────────────────────────────────────────────────────

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(Artisan::class);
    }

    public function operateurDispatch(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operateur_dispatch_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
