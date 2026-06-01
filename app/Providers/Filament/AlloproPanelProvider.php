<?php

namespace App\Providers\Filament;

use App\Filament\Allopro\Pages\Dashboard;
use App\Http\Responses\Allopro\LoginResponse;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
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
        return $panel
            ->id('allopro')
            ->path('allopro')
            ->login()
            ->brandName('AlloPro 24/24 — Centre de Contact')
            ->colors([
                'primary' => Color::Orange,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'info'    => Color::Sky,
                'gray'    => Color::Slate,
            ])
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
            ])
            ->authMiddleware([Authenticate::class])
            ->authGuard('web')
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->spa();
    }
}
