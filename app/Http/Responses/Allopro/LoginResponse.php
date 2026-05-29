<?php
namespace App\Http\Responses\Allopro;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        $user = auth()->user();

        return redirect()->to(match (true) {

            $user->hasAnyRole(['operateur_n1', 'back_office']) =>
                route('filament.allopro.resources.tickets.index'),

            // responsable_plateau et administrateur → dashboard
            default =>
                route('filament.allopro.pages.dashboard'),
        });
    }
}
