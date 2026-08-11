<?php

namespace App\Http\Middleware\GraphQL;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware HTTP pour GraphQL (Lighthouse) — couche d'autorisation Spatie.
 *
 * Ce middleware HTTP est placé dans la pile de route de Lighthouse (config/lighthouse.php),
 * APRÈS AttemptAuthentication (qui résout l'utilisateur via Sanctum) et APRÈS throttle:api
 * (rate limiting). Il applique les mêmes règles d'autorisation qu'en REST :
 *
 *  1. Si l'utilisateur n'est pas authentifié → 401 JSON {"message": "Unauthenticated."}
 *  2. Si l'utilisateur est authentifié mais ne possède aucune permission de lecture
 *     de base (view_prospect OU view_partenaire) → 403 JSON {"message": "This action is unauthorized."}
 *  3. Sinon, on laisse passer — la granularité par champ est gérée par @guard
 *     et les directives @can dans le schéma GraphQL.
 *
 * Mirrors: Requirements 16.3 (same Spatie auth rules as REST) & 16.4 (same rate limiting — handled by throttle:api before this middleware).
 */
class ApplySpatiePermissions
{
    /**
     * Les permissions Spatie minimales requises pour accéder à l'API GraphQL.
     * Un utilisateur doit posséder au moins UNE de ces permissions.
     */
    private const BASELINE_PERMISSIONS = [
        'view_prospect',
        'view_partenaire',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // ── Étape 1 : Vérification de l'authentification ─────────────────
        // AttemptAuthentication tente de résoudre l'utilisateur mais ne lève
        // pas d'exception si aucun token valide n'est fourni. On effectue
        // donc la vérification explicitement ici.
        if ($user === null) {
            return response()->json(
                ['message' => 'Unauthenticated.'],
                Response::HTTP_UNAUTHORIZED,
                ['Content-Type' => 'application/json']
            );
        }

        // ── Étape 2 : Vérification de la permission de base ──────────────
        // L'utilisateur doit posséder au moins une permission de lecture
        // parmi les ressources exposées par le schéma GraphQL.
        $hasBaseline = false;

        foreach (self::BASELINE_PERMISSIONS as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $hasBaseline = true;
                break;
            }
        }

        if (! $hasBaseline) {
            return response()->json(
                ['message' => 'This action is unauthorized.'],
                Response::HTTP_FORBIDDEN,
                ['Content-Type' => 'application/json']
            );
        }

        // ── Étape 3 : Laisser passer ─────────────────────────────────────
        // La granularité fine (par query, par champ) est gérée par les
        // directives @guard(with: ["sanctum"]) dans graphql/schema.graphql.
        return $next($request);
    }
}
