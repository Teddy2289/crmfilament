<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'is_global',
        'imap_host',
        'imap_port',
        'imap_encryption',
        'imap_protocol',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'email',
        'password',
        'from_name',
        'sync_enabled',
        'sync_interval',
        'sync_limit',
        'last_sync_at',
        'active',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'sync_enabled' => 'boolean',
        'active' => 'boolean',
        'last_sync_at' => 'datetime',
        'sync_interval' => 'integer',
        'sync_limit' => 'integer',
    ];

    protected $hidden = [
        'password',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getImapConnectionArray(): array
    {
        // Les identifiants IMAP opérationnels sont centralisés dans .env.
        // IMAP_* peut surcharger MAIL_* si un compte possède une configuration dédiée.
        return [
            'host' => config('imap.host', $this->imap_host),
            'port' => (int) config('imap.port', 993),
            'encryption' => config('imap.encryption', 'ssl'),
            'validate_cert' => (bool) config('imap.validate_cert', true),
            'username' => config('imap.username') ?: $this->email,
            'password' => config('imap.password') ?: $this->password,
            'protocol' => config('imap.protocol', 'imap'),
            'authentication' => config('imap.authentication'),
        ];
    }

    public function getSmtpConnectionArray(): array
    {
        return [
            'host' => $this->smtp_host,
            'port' => $this->smtp_port,
            'encryption' => $this->smtp_encryption,
            'username' => $this->email,
            'password' => $this->password,
            'from' => [
                'address' => $this->email,
                'name' => $this->from_name,
            ],
        ];
    }

    public function getImapEncryptionLabelAttribute(): string
    {
        return match ($this->imap_encryption) {
            'ssl' => 'SSL',
            'tls' => 'TLS',
            'starttls' => 'STARTTLS',
            'none' => 'Aucune',
            default => $this->imap_encryption,
        };
    }

    public function getSmtpEncryptionLabelAttribute(): string
    {
        return match ($this->smtp_encryption) {
            'ssl' => 'SSL',
            'tls' => 'TLS',
            'starttls' => 'STARTTLS',
            'none' => 'Aucune',
            default => $this->smtp_encryption,
        };
    }

    public function getProtocolLabelAttribute(): string
    {
        return match ($this->imap_protocol) {
            'imap' => 'IMAP',
            'pop3' => 'POP3',
            default => $this->imap_protocol,
        };
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeUser($query)
    {
        return $query->where('is_global', false);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeSyncEnabled($query)
    {
        return $query->where('sync_enabled', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('is_global', true);
        });
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function testConnection(): array
    {
        if (! class_exists(\Webklex\PHPIMAP\Client::class)) {
            return [
                'success' => false,
                'message' => 'Le paquet webklex/php-imap est requis pour tester la connexion IMAP.',
            ];
        }

        try {
            $client = new \Webklex\PHPIMAP\Client($this->imap_connection_array);
            $client->connect();
            $client->disconnect();

            return [
                'success' => true,
                'message' => 'Connexion IMAP réussie',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur de connexion: '.$e->getMessage(),
            ];
        }
    }

    public function updateLastSync(): void
    {
        $this->update(['last_sync_at' => now()]);
    }

    public function enableSync(): void
    {
        $this->update(['sync_enabled' => true]);
    }

    public function disableSync(): void
    {
        $this->update(['sync_enabled' => false]);
    }

    public function activate(): void
    {
        $this->update(['active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['active' => false]);
    }

    // ── Relations ────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function ($config) {
            // Chiffrer le mot de passe avant sauvegarde
            if ($config->isDirty('password') && ! empty($config->password)) {
                $config->password = encrypt($config->password);
            }
        });

        static::retrieved(function ($config) {
            // Déchiffrer le mot de passe après récupération
            if (! empty($config->password)) {
                try {
                    $config->password = decrypt($config->password);
                } catch (\Exception $e) {
                    // Si le mot de passe n'est pas chiffré (ancienne version)
                    // On le laisse tel quel
                }
            }
        });
    }
}
