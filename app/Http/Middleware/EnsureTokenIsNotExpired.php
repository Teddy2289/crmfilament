<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware EnsureTokenIsNotExpired
 *
 * Doit être placé APRÈS le middleware auth:sanctum dans la pile, car
 * $request->user() est garanti non-null à ce stade.
 *
 * Vérifie deux cas :
 *  1. Token d'accès expiré     → 401 avec code `token_expired`
 *  2. Refresh token révoqué/expiré → 401 avec code `refresh_token_invalid`
 */
class EnsureTokenIsNotExpired
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token === null) {
            // Sanctum n'a pas résolu de token (ne devrait pas arriver dans le groupe auth:sanctum)
            return $next($request);
        }

        $now = now();

        // ── Cas 1 : refresh token révoqué ou expiré ───────────────────────
        // Le refresh token est identifiable par l'ability 'refresh'.
        if ($token->can('refresh')) {
            $isExpired = $token->expires_at !== null && $token->expires_at->lt($now);

            // Le token Sanctum est déjà supprimé de la BDD si révoqué ;
            // Sanctum ne pourrait alors pas l'avoir résolu — mais on garde
            // la vérification d'expiration explicite pour le code d'erreur dédié.
            if ($isExpired) {
                return response()->json([
                    'message' => 'Refresh token invalid.',
                    'code'    => 'refresh_token_invalid',
                ], 401);
            }

            // Refresh token valide — on laisse passer (l'endpoint /auth/refresh
            // vérifiera l'ability côté contrôleur).
            return $next($request);
        }

        // ── Cas 2 : token d'accès expiré ─────────────────────────────────
        if ($token->expires_at !== null && $token->expires_at->lt($now)) {
            return response()->json([
                'message' => 'Token expired.',
                'code'    => 'token_expired',
            ], 401);
        }

        return $next($request);
    }
}
