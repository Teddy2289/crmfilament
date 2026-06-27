<?php

namespace App\Filament\Themes;

use App\Models\Theme as ThemeModel;
use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme as FilamentTheme;

class SuperAdminTheme implements FilamentTheme
{
    protected ?ThemeModel $theme = null;

    public function __construct()
    {
        $this->theme = ThemeModel::getActiveForPanel('super-admin');
    }

    public function getName(): string
    {
        return 'super-admin';
    }

    public function getLabel(): string
    {
        return $this->theme?->label ?? 'Super Admin';
    }

    public function getColors(): array
    {
        if ($this->theme) {
            return $this->theme->getColors();
        }

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
        if ($this->theme) {
            return $this->theme->getDarkModeColors();
        }

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
