<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceJsonResponse
{
    /**
     * Force l'en-tête Accept: application/json sur toutes les requêtes API,
     * ce qui garantit que Laravel et Sanctum retournent toujours du JSON
     * (et non des redirections HTML) pour les erreurs 401 / 403 / 422…
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
