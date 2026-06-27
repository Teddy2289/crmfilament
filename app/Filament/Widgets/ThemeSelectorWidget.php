<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;

class ThemeSelectorWidget extends Widget
{
    protected static string $view = 'filament.widgets.theme-selector';

    protected static bool $isLazy = false;

    protected static ?int $sort = -1;

    public ?string $theme = null;

    public ?string $mode = null;

    public function mount(): void
    {
        $user = Auth::user();
        $this->theme = $user->theme_preference ?? 'light';
        $this->mode = $user->theme_mode ?? 'light';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('theme')
                    ->label('Thème')
                    ->options([
                        'light' => 'Clair',
                        'dark' => 'Sombre',
                        'system' => 'Système',
                    ])
                    ->default($this->theme)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $user = Auth::user();
                        $user->theme_preference = $state;
                        $user->save();
                        $this->theme = $state;
                        $this->applyTheme();
                    }),
                Select::make('mode')
                    ->label('Mode')
                    ->options([
                        'light' => 'Clair',
                        'dark' => 'Sombre',
                    ])
                    ->default($this->mode)
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $user = Auth::user();
                        $user->theme_mode = $state;
                        $user->save();
                        $this->mode = $state;
                        $this->applyTheme();
                    }),
            ]);
    }

    protected function applyTheme(): void
    {
        // This will be handled by JavaScript in the view
        $this->dispatch('themeChanged', [
            'theme' => $this->theme,
            'mode' => $this->mode,
        ]);
    }
}
