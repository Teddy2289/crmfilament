<?php

namespace App\Filament\Themes;

use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme;

class AlloproTheme implements Theme
{
    public function getName(): string
    {
        return 'allopro';
    }

    public function getLabel(): string
    {
        return 'Allopro';
    }

    public function getColors(): array
    {
        return [
            'primary' => Color::Orange,
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
            'primary' => Color::Orange,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
            'danger' => Color::Rose,
            'info' => Color::Sky,
            'gray' => Color::Slate,
        ];
    }
}
