<?php

namespace App\Filament\Themes;

use App\Models\Theme as ThemeModel;
use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme as FilamentTheme;

class AlloproTheme implements FilamentTheme
{
    protected ?ThemeModel $theme = null;

    public function __construct()
    {
        $this->theme = ThemeModel::getActiveForPanel('allopro');
    }

    public function getName(): string
    {
        return 'allopro';
    }

    public function getLabel(): string
    {
        return $this->theme?->label ?? 'Allopro';
    }

    public function getColors(): array
    {
        if ($this->theme) {
            return $this->theme->getColors();
        }

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
        if ($this->theme) {
            return $this->theme->getDarkModeColors();
        }

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
