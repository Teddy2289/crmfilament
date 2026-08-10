<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictApiDocsInProduction
{
    /**
     * Bloque l'accès à la documentation OpenAPI en environnement de production.
     * En non-production, la requête est transmise normalement.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort(403, 'API documentation is not available in production.');
        }

        return $next($request);
    }
}
