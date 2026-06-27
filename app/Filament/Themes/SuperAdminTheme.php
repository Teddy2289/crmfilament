<?php

namespace App\Filament\Themes;

use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme;

class SuperAdminTheme implements Theme
{
    public function getName(): string
    {
        return 'super-admin';
    }

    public function getLabel(): string
    {
        return 'Super Admin';
    }

    public function getColors(): array
    {
        return [
            'primary' => Color::Purple,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
            'danger' => Color::Rose,
            'info' => Color::Sky,
            'gray' => Color::Slate,
        ];
    }

    public function getDarkModeColors(): array
    {
        return [
            'primary' => Color::Purple,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
            'danger' => Color::Rose,
            'info' => Color::Sky,
            'gray' => Color::Slate,
        ];
    }
}
