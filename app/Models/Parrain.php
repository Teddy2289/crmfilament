<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parrain extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom_prenom',
        'telephone',
        'email',
        'adresse',
        'code_postal',
        'ville',
        'super_parrain',
        'date_creation',
    ];

    protected $casts = [
        'super_parrain' => 'boolean',
        'date_creation' => 'date',
    ];

    public function personnes()
    {
        return $this->hasMany(Client::class, 'parrain_id');
    }
}
