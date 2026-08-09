<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'nom',
        'token',
        'permissions',
        'expires_at',
        'last_used_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpires($query)
    {
        return $query->where('expires_at', '<', now());
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estExpire(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function aPermission(string $permission): bool
    {
        if (empty($this->permissions)) {
            return false;
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    public static function genererToken(): string
    {
        return 'api_' . bin2hex(random_bytes(32));
    }

    public static function creer(int $userId, string $nom, ?array $permissions = null, ?\DateTime $expiresAt = null): self
    {
        return self::create([
            'user_id' => $userId,
            'nom' => $nom,
            'token' => self::genererToken(),
            'permissions' => $permissions,
            'expires_at' => $expiresAt,
        ]);
    }
}
