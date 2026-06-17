<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeuresFormation extends Model
{
    protected $table = 'heures_formations';

    // ✅ Utilisation d'un ID auto-incrémenté standard
    // Si vous voulez garder dossier_id comme clé primaire, décommentez ces lignes
    // protected $primaryKey = 'dossier_id';
    // public $incrementing = false;
    // protected $keyType = 'int';

    protected $fillable = [
        'dossier_id',
        'heures_obligatoires',
        'heures_complementaires',
        'heures_elearning',
        'total_heures',
        'heures_realisees',
        'heures_restantes',
    ];

    protected $casts = [
        'heures_obligatoires' => 'decimal:2',
        'heures_complementaires' => 'decimal:2',
        'heures_elearning' => 'decimal:2',
        'total_heures' => 'decimal:2',
        'heures_realisees' => 'decimal:2',
        'heures_restantes' => 'decimal:2',
    ];

    public function dossier()
    {
        return $this->belongsTo(DossierFormation::class, 'dossier_id', 'id');
    }
}
