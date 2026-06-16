<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoriqueConseiller extends Model
{
    protected $table = 'historique_conseillers';

    protected $fillable = [
        'partenaire_id',
        'ancien_conseiller_id',
        'nouveau_conseiller_id',
        'date_changement',
        'motif',
    ];

    protected $casts = [
        'date_changement' => 'date',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function ancienConseiller()
    {
        return $this->belongsTo(Consultant::class, 'ancien_conseiller_id');
    }

    public function nouveauConseiller()
    {
        return $this->belongsTo(Consultant::class, 'nouveau_conseiller_id');
    }
}
