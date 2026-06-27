<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

class ThemeSelectorWidget extends Widget
{
    protected static string $view = 'filament.widgets.theme-selector';

    protected static bool $isLazy = false;

    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    public ?string $theme = null;

    public ?string $mode = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->theme = $user->theme_preference ?? 'light';
        $this->mode = $user->theme_mode ?? 'light';
    }

    public function saveTheme(): void
    {
        $user = Auth::user();
        $user->theme_preference = $this->theme;
        $user->theme_mode = $this->mode;
        $user->save();
        
        $this->dispatch('closeModal');
        $this->dispatch('themeUpdated');
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('theme')
                ->label('Thème')
                ->options([
                    'light' => 'Clair',
                    'dark' => 'Sombre',
                    'system' => 'Système',
                ])
                ->default($this->theme)
                ->live()
                ->required(),
            Select::make('mode')
                ->label('Mode')
                ->options([
                    'light' => 'Clair',
                    'dark' => 'Sombre',
                ])
                ->default($this->mode)
                ->live()
                ->required(),
        ];
    }
}
