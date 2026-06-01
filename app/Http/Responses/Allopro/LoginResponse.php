<?php

namespace App\Http\Responses\Allopro;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = auth()->user();

        $url = match (true) {
            $user->hasAnyRole(['operateur_n1', 'back_office']) => '/allopro/tickets',
            default => '/allopro',
        };

        return new \Illuminate\Http\RedirectResponse($url);
    }
}
