<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_type',
        'expires_at',
        'calendar_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isExpired();
    }

    /**
     * Retourne le tableau attendu par league/oauth2-google pour refresh
     */
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'expires' => $this->expires_at?->timestamp,
        ]);
    }
}
