<?php

namespace App\Filament\Themes;

use App\Models\Theme as ThemeModel;
use Illuminate\Support\Facades\Auth;

class NsConseilTheme
{
    protected ?ThemeModel $theme = null;

    public function __construct()
    {
        $user = Auth::user();
        
        if ($user && $user->theme_preference && $user->theme_preference !== 'default') {
            $this->theme = ThemeModel::resolveForPanel('ns-conseil', $user);
        }
        
        if (!$this->theme) {
            $this->theme = ThemeModel::getActiveForPanel('ns-conseil');
        }
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
        if ($this->theme?->shouldApplyColors()) {
            return $this->theme->getColors();
        }

        return [];
    }

    public function getDarkModeColors(): array
    {
        if ($this->theme?->shouldApplyColors()) {
            return $this->theme->getDarkModeColors();
        }

        return [];
    }
}
