<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FicheTemplate extends Model
{
    protected $fillable = [
        'type',
        'nom',
        'description',
        'template_path',
        'placeholders',
        'statut_phoning_codes',
        'auto_generation',
        'actif',
    ];

    protected $casts = [
        'placeholders' => 'array',
        'statut_phoning_codes' => 'array',
        'auto_generation' => 'boolean',
        'actif' => 'boolean',
    ];

    const TYPES = [
        'bleue' => 'Bleue (Récap RDV pris)',
        'jaune' => 'Jaune (CSE pas intéressé — rappel J+7)',
        'verte' => 'Verte (RDV à conclure)',
    ];

    const TYPE_COLORS = [
        'bleue' => 'info',
        'jaune' => 'warning',
        'verte' => 'success',
    ];

    const TYPE_ICONS = [
        'bleue' => 'heroicon-o-document-check',
        'jaune' => 'heroicon-o-clock',
        'verte' => 'heroicon-o-arrow-path',
    ];

    /**
     * Placeholders disponibles pour le mapping (champ prospect → variable template).
     */
    const AVAILABLE_PLACEHOLDERS = [
        '${RAISON_SOCIALE}' => 'Raison sociale',
        '${SECTEUR_ACTIVITE}' => "Secteur d'activité",
        '${NB_SALARIES}' => 'Effectif total',
        '${ADRESSE_COMPLETE}' => 'Adresse complète',
        '${DEPARTEMENT}' => 'Département',
        '${VILLE}' => 'Ville',
        '${CODE_POSTAL}' => 'Code postal',
        '${TELEPHONE}' => 'Téléphone standard',
        '${INTERLOCUTEUR_NOM}' => 'Prénom / Nom interlocuteur',
        '${INTERLOCUTEUR_FONCTION}' => 'Fonction interlocuteur',
        '${INTERLOCUTEUR_TELEPHONE}' => 'Tél. direct interlocuteur',
        '${INTERLOCUTEUR_EMAIL}' => 'Email interlocuteur',
        '${TELEPROSPECTEUR}' => 'Téléprospecteur (propriétaire)',
        '${COMMERCIAL}' => 'Responsable de Secteur assigné',
        '${DATE_APPEL}' => 'Date du premier contact',
        '${DATE_GENERATION}' => 'Date de génération de la fiche',
        '${NOTES}' => 'Notes / Description',
        '${RDV_DATE_HEURE}' => 'Date et heure du RDV',
        '${RDV_LIEU}' => 'Lieu du RDV',
        '${CSE_SECRETAIRE}' => 'Secrétaire CSE',
        '${CSE_TRESORIER}' => 'Trésorier CSE',
        '${CSE_NB_ELUS}' => "Nombre d'élus CSE",
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getTypeColorAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'gray';
    }

    public function getTypeIconAttribute(): string
    {
        return self::TYPE_ICONS[$this->type] ?? 'heroicon-o-document';
    }

    public static function actifs(): Collection
    {
        return static::where('actif', true)->get();
    }

    public static function pourType(string $type): Collection
    {
        return static::where('type', $type)
            ->where('actif', true)
            ->get();
    }

    /**
     * Retourne les templates déclenchés par un code statut phoning donné.
     */
    public static function pourStatutPhoning(string $code): Collection
    {
        return static::where('actif', true)
            ->get()
            ->filter(function (self $tpl) use ($code) {
                $codes = $tpl->statut_phoning_codes ?? [];

                return in_array($code, $codes, true);
            })
            ->values();
    }

    /**
     * Retourne les templates auto-générés pour un code statut.
     */
    public static function autoGenerationPourStatut(string $code): Collection
    {
        return static::pourStatutPhoning($code)
            ->filter(fn (self $tpl) => $tpl->auto_generation);
    }
}
