<?php

namespace App\Filament\Themes;

use App\Models\Theme as ThemeModel;
use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme as FilamentTheme;

class NsConseilTheme implements FilamentTheme
{
    protected ?ThemeModel $theme = null;

    public function __construct()
    {
        $this->theme = ThemeModel::getActiveForPanel('ns-conseil');
    }

    public function getName(): string
    {
        return 'ns-conseil';
    }

    public function getLabel(): string
    {
        return $this->theme?->label ?? 'NS Conseil';
    }

    public function getColors(): array
    {
        if ($this->theme) {
            return $this->theme->getColors();
        }

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
        if ($this->theme) {
            return $this->theme->getDarkModeColors();
        }

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
