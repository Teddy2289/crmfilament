<?php

namespace App\Services\Api;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\NewAccessToken;

class AuthService
{
    /**
     * Durée de vie du token d'accès en minutes (depuis config/api.php).
     */
    private int $accessTtl;

    /**
     * Durée de vie du token de rafraîchissement en minutes (depuis config/api.php).
     */
    private int $refreshTtl;

    public function __construct()
    {
        $this->accessTtl  = (int) config('api.token_ttl.access',  60 * 24);       // 24h
        $this->refreshTtl = (int) config('api.token_ttl.refresh', 60 * 24 * 30);  // 30j
    }

    /**
     * Émet une paire de tokens Sanctum (access + refresh) pour l'utilisateur.
     *
     * @return array{access_token: string, refresh_token: string, expires_in: int}
     */
    public function issueTokenPair(User $user): array
    {
        $ip        = request()->ip() ?? 'unknown';
        $now       = Carbon::now();
        $accessExp = $now->copy()->addMinutes($this->accessTtl);
        $refreshExp = $now->copy()->addMinutes($this->refreshTtl);

        /** @var NewAccessToken $accessToken */
        $accessToken = $user->createToken(
            name: 'access',
            abilities: ['*'],
            expiresAt: $accessExp,
        );

        /** @var NewAccessToken $refreshToken */
        $refreshToken = $user->createToken(
            name: 'refresh',
            abilities: ['refresh'],
            expiresAt: $refreshExp,
        );

        Log::info('api.token.issued', [
            'user_id'   => $user->id,
            'ip'        => $ip,
            'timestamp' => $now->toIso8601String(),
        ]);

        return [
            'access_token'  => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'expires_in'    => $this->accessTtl * 60, // en secondes
        ];
    }

    /**
     * Émet un nouveau token d'accès après un rafraîchissement.
     * L'ancien token d'accès (non-refresh) est révoqué.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function refreshAccessToken(User $user): array
    {
        $ip  = request()->ip() ?? 'unknown';
        $now = Carbon::now();

        // Révoquer tous les tokens d'accès existants (pas les refresh)
        $user->tokens()
            ->where('name', 'access')
            ->delete();

        $accessExp = $now->copy()->addMinutes($this->accessTtl);

        /** @var NewAccessToken $accessToken */
        $accessToken = $user->createToken(
            name: 'access',
            abilities: ['*'],
            expiresAt: $accessExp,
        );

        Log::info('api.token.refreshed', [
            'user_id'   => $user->id,
            'ip'        => $ip,
            'timestamp' => $now->toIso8601String(),
        ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'expires_in'   => $this->accessTtl * 60, // en secondes
        ];
    }

    /**
     * Révoque uniquement le token d'accès courant (celui utilisé dans la requête).
     */
    public function revokeAccessToken(User $user): void
    {
        $ip  = request()->ip() ?? 'unknown';
        $now = Carbon::now();

        // currentAccessToken() est le token authentifié sur la requête courante
        $user->currentAccessToken()?->delete();

        Log::info('api.token.revoked', [
            'user_id'   => $user->id,
            'ip'        => $ip,
            'timestamp' => $now->toIso8601String(),
        ]);
    }

    /**
     * Révoque TOUS les tokens (access + refresh) de l'utilisateur.
     */
    public function revokeAllTokens(User $user): void
    {
        $ip  = request()->ip() ?? 'unknown';
        $now = Carbon::now();

        $user->tokens()->delete();

        Log::info('api.token.revoked_all', [
            'user_id'   => $user->id,
            'ip'        => $ip,
            'timestamp' => $now->toIso8601String(),
        ]);
    }
}
