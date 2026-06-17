<?php

namespace App\Http\Responses\NsConseil;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = auth()->user();

        $url = match (true) {
            $user->hasRole('commercial') => '/ns-conseil/partenaires',
            $user->hasRole('teleprospecteur') => '/ns-conseil/prospects',
            default => '/ns-conseil',
        };

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $url]);
        }

        // Pas de contrainte de type ":  Response" ici : l'interface
        // Responsable de Filament n'en exige pas, et Livewire a besoin
        // de récupérer son propre objet Redirector (pas un Symfony\Response
        // classique) pour gérer correctement la redirection en AJAX.
        return redirect()->to($url);
    }
}
