<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EntiteCommerciale extends Model
{
    use SoftDeletes;

    protected $table = 'entite_commerciales';

    protected $fillable = ['code', 'nom'];

    public function consultants()
    {
        return $this->hasMany(Consultant::class, 'entite_id');
    }

    public function campagnes()
    {
        return $this->hasMany(CampagnePhoning::class, 'entite_id');
    }

    public function partenaires()
    {
        return $this->hasMany(Partenaire::class, 'entite_id');
    }

    public function dossierFormations()
    {
        return $this->hasMany(DossierFormation::class, 'entite_id');
    }
}
