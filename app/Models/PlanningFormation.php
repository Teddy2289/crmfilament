<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanningFormation extends Model
{
    protected $table = 'planning_formations';

    protected $primaryKey = 'dossier_id';
    public $incrementing = false;

    protected $fillable = [
        'dossier_id',
        'date_lancement',
        'date_debut',
        'date_fin_theorique',
        'date_certification',
        'date_questionnaire_chaud',
    ];

    protected $casts = [
        'date_lancement'           => 'date',
        'date_debut'               => 'date',
        'date_fin_theorique'       => 'date',
        'date_certification'       => 'date',
        'date_questionnaire_chaud' => 'date',
    ];

    public function dossier()
    {
        return $this->belongsTo(DossierFormation::class, 'dossier_id');
    }
}
