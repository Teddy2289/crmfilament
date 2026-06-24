<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SentEmail extends Model
{
    protected $fillable = [
        'emailable_type',
        'emailable_id',
        'template_cle',
        'sujet',
        'destinataire',
        'cc',
        'corps',
        'envoye_par',
        'envoye_at',
    ];

    protected $casts = [
        'envoye_at' => 'datetime',
    ];

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }

    public function envoyePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'envoye_par');
    }
}
