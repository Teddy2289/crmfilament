<?php

namespace App\Providers\Filament;

use App\Filament\Themes\AdminTheme;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\Theme as ThemeModel;
use App\Http\Middleware\SetLocale;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $theme = ThemeModel::resolveForPanel('admin');
        
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName($theme?->brand_name ?? 'Admin')
            ->brandLogo($theme?->brand_logo_path)
            ->favicon($theme?->favicon_path)
            ->colors(fn (): array => app(AdminTheme::class)->getColors())
            ->defaultThemeMode(ThemeMode::Light)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\ThemeSelectorWidget::class,
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetLocale::class,
                EnsureSuperAdmin::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => ThemeModel::resolveForPanel('admin', auth()->user())?->usesEspoChrome()
                    ? view('filament.shared.espo-theme')
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => auth()->user()?->isSuperAdmin()
                    ? view('filament.shared.admin-button')
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => ($requestTheme = ThemeModel::resolveForPanel('admin', auth()->user()))?->custom_css
                    ? '<style>' . $requestTheme->custom_css . '</style>'
                    : '',
            );
    }
}
