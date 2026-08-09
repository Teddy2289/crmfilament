<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportMapping extends Model
{
    protected $fillable = [
        'user_id',
        'model_type',
        'nom',
        'mapping',
        'options',
    ];

    protected $casts = [
        'mapping' => 'array',
        'options' => 'array',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePourModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    public function scopePourUtilisateur($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
