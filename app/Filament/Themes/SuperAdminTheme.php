<?php

namespace App\Filament\Themes;

use App\Models\Theme as ThemeModel;
use Illuminate\Support\Facades\Auth;

class SuperAdminTheme
{
    protected ?ThemeModel $theme = null;

    public function __construct()
    {
        $user = Auth::user();
        
        if ($user && $user->theme_preference && $user->theme_preference !== 'default') {
            $this->theme = ThemeModel::resolveForPanel('super-admin', $user);
        }
        
        if (!$this->theme) {
            $this->theme = ThemeModel::getActiveForPanel('super-admin');
        }
    }

    public function getName(): string
    {
        return 'super-admin';
    }

    public function getLabel(): string
    {
        return $this->theme?->label ?? 'Super administrateur';
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
