<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Activité PERMANENCE liée à un partenaire + consultant.
 * Séparée de ActiviteVente pour couvrir les champs spécifiques
 * (prc_2026, rdv_physique, rdv_telephonique — onglet DRAFT).
 */
class ActivitePermanence extends Model
{
    protected $table = 'activite_permanences';

    protected $fillable = [
        'partenaire_id',
        'consultant_id',
        'derniere_permanence',
        'nbre_2025',
        'nbre_2026',
        'prc_2026',
        'rdv_physique',
        'rdv_telephonique',
    ];

    protected $casts = [
        'derniere_permanence' => 'date',
        'nbre_2025'           => 'integer',
        'nbre_2026'           => 'integer',
        'prc_2026'            => 'integer',
        'rdv_physique'        => 'integer',
        'rdv_telephonique'    => 'integer',
    ];

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }
}
