<?php

namespace App\Filament\Widgets;

use App\Models\Theme;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

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
        $this->theme = $user->theme_preference ?? 'default';
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
        $currentPanel = Filament::getCurrentPanel()->getId();
        $availableThemes = Theme::where('panel', $currentPanel)
            ->where('is_active', true)
            ->pluck('label', 'name')
            ->toArray();

        return [
            Select::make('theme')
                ->label('Thème')
                ->options([
                    'default' => 'Filament natif par defaut',
                    ...$availableThemes,
                ])
                ->default($this->theme)
                ->live()
                ->required()
                ->helperText('Choisissez un theme uniquement si vous voulez personnaliser l\'interface.'),
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
