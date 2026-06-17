<?php

namespace App\Filament\Allopro\Pages\Auth;

use Filament\Pages\Auth\Login as FilamentLogin;

class Login extends FilamentLogin
{
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Se connecter';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Accédez à votre espace opérateur AlloPro';
    }
}
