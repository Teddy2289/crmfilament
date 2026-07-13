<?php

namespace App\Providers\Filament;

use App\Filament\NsConseil\Pages\Auth\Login as NsConseilLogin;
use App\Filament\NsConseil\Pages\Dashboard;
use App\Filament\NsConseil\Pages\RingoverDashboard;
use App\Filament\NsConseil\Resources\PartenaireResource\Pages\PartenaireKanban;
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
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

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
        $theme = ThemeModel::resolveForPanel('ns-conseil');

        return $panel
            ->id('ns-conseil')
            ->path('ns-conseil')
            ->login(NsConseilLogin::class)
            ->brandName($theme?->brand_name ?? 'NS CONSEIL — CRM Partenaires')
            ->brandLogo(fn() => view('filament.ns-conseil.brand-logo', [
                'logoPath' => $theme?->brand_logo_path,
            ]))
            ->brandLogoHeight('3.5rem')
            ->favicon($theme?->favicon_path)
            ->colors([
                'primary'       => Color::hex('#2C4A5E'), 
                'gray'          => Color::Slate,
                'secondary'     => Color::hex('#3F8FA3'),
                'info'          => Color::hex('#3F8FA3'),
                'custom-kanban' => Color::hex('#F3DCB0'), 
                'success'       => Color::hex('#D9A455'),
                'warning'       => Color::hex('#E8B873'), 
                'danger'        => Color::Rose,
            
            ])
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
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(false)
                    ->timezone(config('app.timezone', 'Europe/Paris'))
                    ->locale('fr'),
            ])
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
                PartenaireKanban::class
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

            // ── Thème EspoCRM si activé en base ──────────────────────
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => ThemeModel::resolveForPanel('ns-conseil', auth()->user())?->usesEspoChrome()
                    ? view('filament.shared.espo-theme')
                    : '',
            )

            // ── Thème NS Conseil (Marine & Doré) ─────────────────────
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => view('filament.ns-conseil.theme'),
            )

            // ── Loading overlay ──────────────────────────────────────
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn() => view('filament.loading-overlay'),
            )

            // ── CSS custom depuis la base de données ─────────────────
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn() => ($requestTheme = ThemeModel::resolveForPanel('ns-conseil', auth()->user()))?->custom_css
                    ? '<style>' . $requestTheme->custom_css . '</style>'
                    : '',
            );
    }
}
