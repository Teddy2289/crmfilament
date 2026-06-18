<?php

namespace App\Filament\Allopro\Pages\Auth;

use Filament\Pages\Auth\Login as FilamentLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends FilamentLogin
{
    public function getHeading(): string|Htmlable
    {
        return 'Se connecter';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Accédez à votre espace opérateur AlloPro';
    }
}
