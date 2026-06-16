<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutresInterlocuteurs extends Model
{
    protected $table = 'autres_interlocuteurs';

    protected $fillable = [
        'partenaire_id',
        'texte_libre',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }
}
