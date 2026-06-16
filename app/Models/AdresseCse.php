<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdresseCse extends Model
{
    protected $table = 'adresse_cses';

    protected $fillable = [
        'partenaire_id',
        'adresse',
        'code_postal',
        'commune',
    ];

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }
}
