<?php

namespace App\Providers\Filament;

use App\Filament\Allopro\Pages\Auth\Login as AlloproLogin;
use App\Filament\Themes\AlloproTheme;
use App\Http\Responses\Allopro\LoginResponse;
use App\Models\Theme as ThemeModel;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AlloproPanelProvider extends PanelProvider
{
    public function register(): void
    {
        parent::register();

        $this->app->bind(
            LoginResponseContract::class,
            LoginResponse::class,
        );
    }

    public function panel(Panel $panel): Panel
    {
        $theme = ThemeModel::resolveForPanel('allopro');
        
        return $panel
            ->id('allopro')
            ->path('allopro')
            ->login(AlloproLogin::class)
            ->brandName($theme?->brand_name ?? 'AlloPro 24/24 — Centre de Contact')
            ->brandLogo($theme?->brand_logo_path)
            ->favicon($theme?->favicon_path)
            ->colors(fn (): array => app(AlloproTheme::class)->getColors())
            ->defaultThemeMode(ThemeMode::Light)
            ->navigationGroups([
                NavigationGroup::make('Tickets')
                    ->icon('heroicon-o-ticket'),
                NavigationGroup::make('Artisans')
                    ->icon('heroicon-o-wrench-screwdriver'),
                NavigationGroup::make('Qualité')
                    ->icon('heroicon-o-star'),
                NavigationGroup::make('Prospection')
                    ->icon('heroicon-o-megaphone'),
                NavigationGroup::make('Administration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->discoverResources(
                in: app_path('Filament/Allopro/Resources'),
                for: 'App\\Filament\\Allopro\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Allopro/Pages'),
                for: 'App\\Filament\\Allopro\\Pages'
            )
            ->pages([])
            ->discoverWidgets(
                in: app_path('Filament/Allopro/Widgets'),
                for: 'App\\Filament\\Allopro\\Widgets'
            )
            ->widgets([
                \App\Filament\Widgets\ThemeSelectorWidget::class,
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
            ])
            ->authMiddleware([Authenticate::class])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => ThemeModel::resolveForPanel('allopro', auth()->user())?->usesEspoChrome()
                    ? view('filament.shared.espo-theme')
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.allopro.auth.login-styles'),
                scopes: [AlloproLogin::class],
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.allopro.auth.login-sidebar'),
                scopes: [AlloproLogin::class],
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => ($requestTheme = ThemeModel::resolveForPanel('allopro', auth()->user()))?->custom_css
                    ? '<style>' . $requestTheme->custom_css . '</style>'
                    : '',
            );
    }
}
