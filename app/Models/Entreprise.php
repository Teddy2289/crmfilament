<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entreprise extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'raison_sociale',
        'siret',
        'siren',
        'numero_tva',
        'forme_juridique',
        'capital',
        'adresse',
        'code_postal',
        'ville',
        'pays',
        'telephone',
        'email',
        'site_web',
        'secteur_activite',
        'effectif',
        'code_naf',
        'date_creation',
        'description',
        'extra_data',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'extra_data' => 'array',
    ];

    public function partenaires(): HasMany
    {
        return $this->hasMany(Partenaire::class);
    }

    public function clients(): HasManyThrough
    {
        return $this->hasManyThrough(Client::class, Partenaire::class, 'entreprise_id', 'partenaire_id');
    }
}
