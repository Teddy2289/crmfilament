<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Activité VENTE liée à un partenaire + consultant.
 * (anciennement fusionné avec permanence — maintenant séparé selon le MEA)
 */
class ActiviteVente extends Model
{
    protected $table = 'activite_ventes';

    protected $fillable = [
        'partenaire_id',
        'consultant_id',
        'nombre_ventes_total',
        'derniere_vente',
        'ventes_2025',
        'ventes_2026',
    ];

    protected $casts = [
        'nombre_ventes_total' => 'integer',
        'ventes_2025' => 'integer',
        'ventes_2026' => 'integer',
        'derniere_vente' => 'date',
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
