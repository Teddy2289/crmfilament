<?php

namespace App\Filament\NsConseil\Pages\Auth;

use Filament\Pages\Auth\Login as FilamentLogin;

class Login extends FilamentLogin
{
    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return 'Se connecter';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Accédez à votre espace de travail NS Conseil';
    }
}
