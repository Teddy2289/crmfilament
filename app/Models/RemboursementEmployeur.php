<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemboursementEmployeur extends Model
{
    protected $table = 'remboursements_employeur';

    protected $fillable = [
        'partenaire_id',
        'date_demande',
        'montant',
        'commentaires',
    ];

    protected $casts = [
        'date_demande' => 'date',
        'montant'      => 'decimal:2',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }
}
