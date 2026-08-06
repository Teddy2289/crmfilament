<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RendezVousAssociation extends Model
{
    protected $table = 'rdv_associations';

    protected $fillable = [
        'rendez_vous_id',
        'rdvable_type',
        'rdvable_id',
        'method',
        'user_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function rendezVous()
    {
        return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Polymorphic accessor to the associated model (Prospect/Partenaire/Client)
     */
    public function rdvable()
    {
        return $this->morphTo();
    }
}
