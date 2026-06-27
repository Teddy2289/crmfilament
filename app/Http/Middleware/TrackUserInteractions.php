<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\HistoriqueInteractionUser;
use Symfony\Component\HttpFoundation\Response;

class TrackUserInteractions
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Track interactions for Filament resource views
        if (Auth::check() && $request->is('ns-conseil/*')) {
            $this->trackInteraction($request);
        }

        return $response;
    }

    protected function trackInteraction(Request $request): void
    {
        $path = $request->path();
        $user = Auth::user();

        // Extract entity type and ID from URL
        if (preg_match('#^ns-conseil/(prospects|partenaires|clients|opportunites)/(\d+)$#', $path, $matches)) {
            $entityType = match($matches[1]) {
                'prospects' => \App\Models\Prospect::class,
                'partenaires' => \App\Models\Partenaire::class,
                'clients' => \App\Models\Client::class,
                'opportunites' => \App\Models\Opportunite::class,
                default => null,
            };

            if ($entityType) {
                HistoriqueInteractionUser::enregistrerInteraction(
                    $entityType::find($matches[2]),
                    $user->id,
                    'consultation',
                    'Consultation de la fiche',
                    ['url' => $path]
                );
            }
        }
    }
}
