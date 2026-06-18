<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleOAuthController extends Controller
{
    public function __construct(private GoogleCalendarService $google) {}

    public function redirect()
    {
        // Sauvegarde l'URL de retour
        session(['url.intended' => url()->previous()]);

        return redirect($this->google->getAuthorizationUrl());
    }

    public function callback(Request $request)
    {
        // Retour direct vers la page calendar sans passer par Filament SPA
        $returnUrl = '/ns-conseil/calendar';

        if ($request->has('error')) {
            return redirect($returnUrl)->with('error', $request->get('error_description', 'Autorisation refusée.'));
        }

        $code = $request->get('code');
        $user = Auth::user();

        if (! $code || ! $user) {
            return redirect($returnUrl)->with('error', 'Code OAuth manquant.');
        }

        try {
            $this->google->exchangeCode($code, $user);

            // Flash en session — sera affiché par Filament au prochain rendu
            session()->flash('google_connected', true);

        } catch (\Throwable $e) {
            session()->flash('google_error', $e->getMessage());
        }

        // ✅ Redirect classique HTTP — pas de route() Filament SPA
        return redirect($returnUrl);
    }

    public function disconnect()
    {
        $this->google->revokeToken(Auth::user());

        session()->flash('google_disconnected', true);

        return redirect('/ns-conseil/calendar');
    }
}
