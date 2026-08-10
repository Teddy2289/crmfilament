<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Integration extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'name',
        'description',
        'config',
        'user_id',
        'actif',
        'verified',
        'last_sync_at',
    ];

    protected $casts = [
        'config' => 'array',
        'actif' => 'boolean',
        'verified' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeSlack($query)
    {
        return $query->where('type', 'slack');
    }

    public function scopeTeams($query)
    {
        return $query->where('type', 'teams');
    }

    public function scopeOutlook($query)
    {
        return $query->where('type', 'outlook');
    }

    public function scopeCrm($query)
    {
        return $query->where('type', 'crm');
    }

    public function sendNotification(string $message, array $data = []): bool
    {
        return match($this->type) {
            'slack' => $this->sendSlackNotification($message, $data),
            'teams' => $this->sendTeamsNotification($message, $data),
            'outlook' => $this->sendOutlookNotification($message, $data),
            'crm' => $this->sendCrmNotification($message, $data),
            default => false,
        };
    }

    protected function sendSlackNotification(string $message, array $data): bool
    {
        $webhookUrl = $this->config['webhook_url'] ?? null;
        if (!$webhookUrl) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => $message,
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => $message,
                        ],
                    ],
                ],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function sendTeamsNotification(string $message, array $data): bool
    {
        $webhookUrl = $this->config['webhook_url'] ?? null;
        if (!$webhookUrl) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => $message,
                'title' => $this->name,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function sendOutlookNotification(string $message, array $data): bool
    {
        // Implementation pour Outlook Graph API
        // Nécessite OAuth2 et configuration complexe
        return false;
    }

    protected function sendCrmNotification(string $message, array $data): bool
    {
        // Implementation pour CRM tiers (HubSpot, Salesforce, etc.)
        $apiUrl = $this->config['api_url'] ?? null;
        $apiKey = $this->config['api_key'] ?? null;
        
        if (!$apiUrl || !$apiKey) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'message' => $message,
                'data' => $data,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function verify(): bool
    {
        $result = match($this->type) {
            'slack' => $this->verifySlack(),
            'teams' => $this->verifyTeams(),
            'outlook' => $this->verifyOutlook(),
            'crm' => $this->verifyCrm(),
            default => false,
        };

        if ($result) {
            $this->update(['verified' => true, 'last_sync_at' => now()]);
        }

        return $result;
    }

    protected function verifySlack(): bool
    {
        $webhookUrl = $this->config['webhook_url'] ?? null;
        if (!$webhookUrl) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => 'Test de connexion CRM',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function verifyTeams(): bool
    {
        $webhookUrl = $this->config['webhook_url'] ?? null;
        if (!$webhookUrl) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::post($webhookUrl, [
                'text' => 'Test de connexion CRM',
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function verifyOutlook(): bool
    {
        // Implementation pour Outlook Graph API
        return false;
    }

    protected function verifyCrm(): bool
    {
        $apiUrl = $this->config['api_url'] ?? null;
        $apiKey = $this->config['api_key'] ?? null;
        
        if (!$apiUrl || !$apiKey) return false;

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
            ])->get($apiUrl . '/health');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
