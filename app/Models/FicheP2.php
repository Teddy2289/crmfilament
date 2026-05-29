<?php

namespace App\Models;

use App\Enums\CorpsDeMetier;
use App\Enums\AncienneteProbleme;
use App\Enums\NiveauPriorite;
use App\Enums\TypeLogement;
use App\Enums\StatutOccupant;
use Illuminate\Database\Eloquent\Model;

class FicheP2 extends Model
{
    protected $table = 'fiche_p2s';

    protected $casts = [
        'corps_de_metier' => CorpsDeMetier::class,
        'anciennete_probleme' => AncienneteProbleme::class,
        'niveau_priorite' => NiveauPriorite::class,
        'type_logement' => TypeLogement::class,
        'statut_occupant' => StatutOccupant::class,
        'presence_client' => 'boolean',
        'fiche_complete' => 'boolean',
        'reponses_metier' => 'array',
    ];

    protected $fillable = [
        'ticket_id', 'corps_de_metier', 'nature_probleme',
        'description_detaillee', 'localisation_precise', 'anciennete_probleme',
        'niveau_priorite', 'justificatif_priorite',
        'presence_client', 'type_logement', 'statut_occupant',
        'nom_client', 'telephone_client', 'adresse_intervention',
        'reponses_metier', 'fiche_complete',
    ];

    // ── Validation ───────────────────────────────────────────────
    public function isDescriptionValide(): bool
    {
        return strlen($this->description_detaillee ?? '') >= 30;
    }

    public function isFicheComplete(): bool
    {
        $required = [
            'corps_de_metier', 'nature_probleme', 'description_detaillee',
            'localisation_precise', 'anciennete_probleme', 'niveau_priorite',
            'justificatif_priorite', 'type_logement', 'statut_occupant',
            'nom_client', 'telephone_client', 'adresse_intervention',
        ];

        foreach ($required as $field) {
            if (empty($this->$field)) return false;
        }

        return $this->isDescriptionValide();
    }

    // ── Questions métier ─────────────────────────────────────────
    public function getQuestionsMetier(): array
    {
        return $this->corps_de_metier->questionsMetier();
    }

    // ── Accesseurs ───────────────────────────────────────────────
    public function getMetierLabelAttribute(): string
    {
        return $this->corps_de_metier->label();
    }

    public function getPrioriteLabelAttribute(): string
    {
        return $this->niveau_priorite->label();
    }

    public function getChampsManquantsAttribute(): array
    {
        $required = [
            'corps_de_metier', 'nature_probleme', 'description_detaillee',
            'localisation_precise', 'anciennete_probleme', 'niveau_priorite',
            'justificatif_priorite', 'type_logement', 'statut_occupant',
            'nom_client', 'telephone_client', 'adresse_intervention',
        ];

        return collect($required)
            ->filter(fn($field) => empty($this->$field))
            ->values()
            ->toArray();
    }

    // ── Boot ─────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::saving(function (FicheP2 $fiche) {
            $fiche->fiche_complete = $fiche->isFicheComplete();
        });
    }

    // ── Relations ────────────────────────────────────────────────
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
