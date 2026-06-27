<?php

namespace App\Filament\Themes;

use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme;

class NsConseilTheme implements Theme
{
    public function getName(): string
    {
        return 'ns-conseil';
    }

    public function getLabel(): string
    {
        return 'NS Conseil';
    }

    public function getColors(): array
    {
        return [
            'primary' => Color::Blue,
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
            'primary' => Color::Blue,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
            'danger' => Color::Rose,
            'info' => Color::Sky,
            'gray' => Color::Slate,
        ];
    }
}
