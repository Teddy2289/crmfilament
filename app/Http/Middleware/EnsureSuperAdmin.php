<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->actif) {
            abort(403);
        }

        // Utilise Spatie directement via roles relation
        $roles = $user->roles->pluck('name');

        if (!$roles->intersect(['super_admin', 'administrateur'])->count()) {
            abort(403, 'Accès réservé aux super administrateurs.');
        }

        return $next($request);
    }
}
