<?php

namespace App\Providers\Filament;

use App\Filament\NsConseil\Pages\AircallDashboard;
use App\Http\Responses\NsConseil\LoginResponse;
use App\Filament\NsConseil\Pages\Dashboard;
use App\Http\Middleware\SetLocale;
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
        return $panel
            ->id('ns-conseil')
            ->path('ns-conseil')
            ->login()
            ->brandName('NS CONSEIL — CRM Partenaires')
            ->colors([
                'primary' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
                'gray'    => Color::Slate,
            ])
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
                AircallDashboard::class,
            ])
            ->widgets([])
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
            ->authMiddleware([Authenticate::class])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa()
            // ✅ CORRECT
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn() => view('filament.loading-overlay'),
            )
            ->renderHook(
                PanelsRenderHook::TOPBAR_END,
                fn() => auth()->user()?->isSuperAdmin()
                    ? view('filament.shared.admin-button')
                    : '',
            );
    }
}
