<?php

namespace App\Filament\Themes;

use App\Models\Theme as ThemeModel;
use Filament\Support\Colors\Color;
use Filament\Support\Themes\Contracts\Theme as FilamentTheme;
use Illuminate\Support\Facades\Auth;

class AdminTheme implements FilamentTheme
{
    protected ?ThemeModel $theme = null;

    public function __construct()
    {
        $user = Auth::user();
        
        if ($user && $user->theme_preference && $user->theme_preference !== 'default') {
            $this->theme = ThemeModel::where('name', $user->theme_preference)
                ->where('panel', 'admin')
                ->where('is_active', true)
                ->first();
        }
        
        if (!$this->theme) {
            $this->theme = ThemeModel::getActiveForPanel('admin');
        }
    }

    public function getName(): string
    {
        return 'admin';
    }

    public function getLabel(): string
    {
        return $this->theme?->label ?? 'Admin';
    }

    public function getColors(): array
    {
        if ($this->theme) {
            return $this->theme->getColors();
        }

        return [
            'primary' => Color::Indigo,
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
            'primary' => Color::Indigo,
            'success' => Color::Emerald,
            'warning' => Color::Amber,
            'danger' => Color::Rose,
            'info' => Color::Sky,
            'gray' => Color::Slate,
        ];
    }
}
