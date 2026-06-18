<?php

namespace App\Models;

use App\Enums\AncienneteProbleme;
use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use App\Enums\StatutOccupant;
use App\Enums\TypeLogement;
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
        'bascule_p5_requise' => 'boolean',
        'fiche_complete' => 'boolean',
        'reponses_metier' => 'array',
        'date_qualification_complete' => 'datetime',
        'duree_appel_p2' => 'integer',
    ];

    protected $fillable = [
        // Section A : Identification du contact
        'ticket_id',
        'nom_client',
        'telephone_client',
        'email_client',
        'adresse_intervention',
        'code_postal_ville',
        'canal_contact_preferentiel',

        // Section B : Identification métier & problème
        'corps_de_metier',
        'nature_probleme',
        'description_detaillee',
        'localisation_precise',
        'anciennete_probleme',
        'reponses_metier',

        // Section C : Priorité et bascule P5
        'niveau_priorite',
        'justificatif_priorite',
        'bascule_p5_requise',

        // Section D : Logement et présence
        'presence_client',
        'type_logement',
        'statut_occupant',
        'garantie_contrat',
        'code_acces_interphone',
        'contact_alternatif',
        'etage_ascenseur',

        // Section F : Champs auto (remplis par le système)
        'agent_qualificateur_id',
        'date_qualification_complete',
        'duree_appel_p2',
        'source_appel_ligne',

        'fiche_complete',
    ];

    // ── Validation ───────────────────────────────────────────────

    public function isDescriptionValide(): bool
    {
        return strlen($this->description_detaillee ?? '') >= 30;
    }

    public function isFicheComplete(): bool
    {
        $required = [
            'corps_de_metier',
            'nature_probleme',
            'description_detaillee',
            'localisation_precise',
            'anciennete_probleme',
            'niveau_priorite',
            'justificatif_priorite',
            'type_logement',
            'statut_occupant',
            'nom_client',
            'telephone_client',
            'adresse_intervention',
            'code_postal_ville',
            'canal_contact_preferentiel',
            'garantie_contrat',
        ];

        foreach ($required as $field) {
            if (empty($this->$field)) {
                return false;
            }
        }

        // bascule_p5_requise doit avoir été explicitement défini (true ou false)
        if ($this->bascule_p5_requise === null) {
            return false;
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

    public function getNecessiteP5Attribute(): bool
    {
        return (bool) $this->bascule_p5_requise;
    }

    public function getChampsManquantsAttribute(): array
    {
        $required = [
            'corps_de_metier',
            'nature_probleme',
            'description_detaillee',
            'localisation_precise',
            'anciennete_probleme',
            'niveau_priorite',
            'justificatif_priorite',
            'type_logement',
            'statut_occupant',
            'nom_client',
            'telephone_client',
            'adresse_intervention',
            'code_postal_ville',
            'canal_contact_preferentiel',
            'garantie_contrat',
        ];

        $manquants = collect($required)
            ->filter(fn ($field) => empty($this->$field))
            ->values()
            ->toArray();

        if ($this->bascule_p5_requise === null) {
            $manquants[] = 'bascule_p5_requise';
        }

        return $manquants;
    }

    // ── Boot ─────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (FicheP2 $fiche) {
            $fiche->fiche_complete = $fiche->isFicheComplete();

            if (! $fiche->date_qualification_complete && $fiche->fiche_complete) {
                $fiche->date_qualification_complete = now();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agentQualificateur()
    {
        return $this->belongsTo(User::class, 'agent_qualificateur_id');
    }
}
