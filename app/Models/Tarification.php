<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tarification extends Model
{
    protected $fillable = [
        'partenaire_id',
        'prix_pc',
        'part_aopia',
        // ✅ Ajout MEA : tarif net salarié et tarif affiché sur comm
        'tarifs',
        'tarifs_affichage_comm',
        'part_cse',
        'part_salarie',
        'adresse_facturation',
    ];

    protected $casts = [
        'prix_pc' => 'decimal:2',
        'part_aopia' => 'decimal:2',
        'tarifs' => 'decimal:2',
        'tarifs_affichage_comm' => 'decimal:2',
        'part_cse' => 'decimal:2',
        'part_salarie' => 'decimal:2',
    ];

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }
}
