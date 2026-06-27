<?php

namespace App\Providers\Filament;

use App\Filament\Themes\NsConseilTheme;
use App\Filament\NsConseil\Pages\Auth\Login as NsConseilLogin;
use App\Filament\NsConseil\Pages\Dashboard;
use App\Filament\NsConseil\Pages\RingoverDashboard;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackUserInteractions;
use App\Http\Responses\NsConseil\LoginResponse;
use App\Models\Theme as ThemeModel;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
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
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;  // ← AJOUT

class NsConseilPanelProvider extends PanelProvider
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
        $theme = ThemeModel::getActiveForPanel('ns-conseil');
        
        return $panel
            ->id('ns-conseil')
            ->path('ns-conseil')
            ->login(NsConseilLogin::class)
            ->brandName($theme?->brand_name ?? 'NS CONSEIL — CRM Partenaires')
            ->brandLogo($theme?->brand_logo_path)
            ->favicon($theme?->favicon_path)
            ->theme(NsConseilTheme::class)
            ->defaultThemeMode(ThemeMode::Light)
            ->navigationGroups([
                NavigationGroup::make('Pipeline')
                    ->icon('heroicon-o-chart-bar'),
                NavigationGroup::make('Contacts')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make('Activités')
                    ->icon('heroicon-o-phone'),
                NavigationGroup::make('Clients & Formations')
                    ->icon('heroicon-o-academic-cap'),
                NavigationGroup::make('Administration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            // ── AJOUT : plugin FullCalendar ──────────────────────────
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(false)
                    ->timezone(config('app.timezone', 'Europe/Paris'))
                    ->locale('fr'),
            ])
            // ────────────────────────────────────────────────────────
            ->discoverResources(
                in: app_path('Filament/NsConseil/Resources'),
                for: 'App\\Filament\\NsConseil\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/NsConseil/Pages'),
                for: 'App\\Filament\\NsConseil\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/NsConseil/Widgets'),
                for: 'App\\Filament\\NsConseil\\Widgets'
            )
            ->pages([
                Dashboard::class,
                RingoverDashboard::class,
            ])
            ->widgets([
                \App\Filament\Widgets\ThemeSelectorWidget::class,
                \App\Livewire\TeamLeaderStatsWidget::class,
                \App\Livewire\TeamLeaderChartWidget::class,
                \App\Livewire\TeamLeaderUserStatsWidget::class,
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
                TrackUserInteractions::class,
            ])
            ->authMiddleware([Authenticate::class])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa()
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.shared.espo-theme'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => view('filament.ns-conseil.auth.login-styles'),
                scopes: [NsConseilLogin::class],
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.ns-conseil.auth.login-sidebar'),
                scopes: [NsConseilLogin::class],
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn () => view('filament.loading-overlay'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn () => auth()->user()?->isSuperAdmin()
                    ? view('filament.shared.admin-button')
                    : '',
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => $theme?->custom_css 
                    ? '<style>' . $theme->custom_css . '</style>' 
                    : '',
            );
    }
}
