<?php

namespace App\Filament\NsConseil\Pages\Auth;

use Filament\Pages\Auth\Login as FilamentLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends FilamentLogin
{
    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Accédez à votre espace de travail NS Conseil';
    }
}
