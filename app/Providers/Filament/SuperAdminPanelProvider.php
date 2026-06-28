<?php

namespace App\Providers\Filament;

use App\Filament\SuperAdmin\Pages\Dashboard;
use App\Filament\SuperAdmin\Pages\DatabaseManager;
use App\Filament\Themes\SuperAdminTheme;
use App\Http\Middleware\EnsureSuperAdmin;
use App\Models\Theme as ThemeModel;
use App\Http\Middleware\SetLocale;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $theme = ThemeModel::resolveForPanel('super-admin');
        
        return $panel
            ->id('super-admin')
            ->path('super-admin')
            ->login()
            ->brandName($theme?->brand_name ?? '⚙️ Super Administration')
            ->brandLogo($theme?->brand_logo_path)
            ->favicon($theme?->favicon_path)
            ->colors(fn (): array => app(SuperAdminTheme::class)->getColors())
            ->defaultThemeMode(ThemeMode::Light)
            ->navigationGroups([
                NavigationGroup::make('Utilisateurs & Accès')
                    ->icon('heroicon-o-shield-check'),
                NavigationGroup::make('Paramétrage CRM')
                    ->icon('heroicon-o-adjustments-horizontal'),
                NavigationGroup::make('Base de données')
                    ->icon('heroicon-o-circle-stack'),
                NavigationGroup::make('Système')
                    ->icon('heroicon-o-cog-6-tooth'),
                NavigationGroup::make('Logs & Audit')
                    ->icon('heroicon-o-document-magnifying-glass'),
            ])
            ->discoverResources(
                in: app_path('Filament/SuperAdmin/Resources'),
                for: 'App\\Filament\\SuperAdmin\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/SuperAdmin/Pages'),
                for: 'App\\Filament\\SuperAdmin\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/SuperAdmin/Widgets'),
                for: 'App\\Filament\\SuperAdmin\\Widgets'
            )
            ->pages([
                Dashboard::class,
                DatabaseManager::class,
            ])
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
                SetLocale::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureSuperAdmin::class,
            ])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => ThemeModel::resolveForPanel('super-admin', auth()->user())?->usesEspoChrome()
                    ? view('filament.shared.espo-theme')
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => ($requestTheme = ThemeModel::resolveForPanel('super-admin', auth()->user()))?->custom_css
                    ? '<style>' . $requestTheme->custom_css . '</style>'
                    : '',
            );
    }
}
