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
use App\Services\Crm\CrmSettingsService;
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
                // Palette Tailwind complète (blue, orange, indigo, teal, violet, ...)
                // enregistrée en premier : c'est ce qui permet aux badges de statut
                // (App\Enums\OrganizationStatus, OrganizationType, ProspectStatut,
                // Client::etatColor, ...) d'utiliser des noms de couleur "bruts" et
                // d'être réellement colorés. Sans ce registre, Filament ne génère
                // aucune variable CSS pour un nom de couleur qui n'est pas déclaré
                // ici, et le badge s'affiche sans couleur.
                ...Color::all(),

                // Alias de marque NS Conseil — déclarés après pour prendre le pas
                // sur les valeurs par défaut de Color::all() ci-dessus.
                'primary'       => Color::hex('#2C4A5E'),
                'gray'          => Color::Slate,
                'secondary'     => Color::hex('#3F8FA3'),
                'info'          => Color::hex('#00d2fc'),
                'custom-kanban' => Color::hex('#F3DCB0'),
                'success'       => Color::hex('#D9A455'),
                'warning'       => Color::hex('#E8B873'),
                'rattachement'   => Color::hex('#00c9a7'),
                'programme'      => Color::hex('#ff8066'),
                'green'         => Color::Emerald,
                'danger'        => Color::Rose,

            ])
            ->defaultThemeMode(ThemeMode::Light)
            ->navigationGroups(static::buildNavigationGroups())
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

    /**
     * Clé du réglage CrmSetting (groupe "navigation") qui stocke l'ordre
     * choisi par le super-admin pour les groupes de menu ci-dessous.
     */
    public const NAVIGATION_ORDER_SETTING_KEY = 'navigation.ns_conseil_group_order';

    /**
     * Groupes de menu gérables depuis le back-office super-admin, avec
     * leur icône et leur état replié par défaut.
     *
     * @return array<string, array{icon: string, collapsed: bool}>
     */
    public static function navigationGroupDefinitions(): array
    {
        return [
            'Activités' => ['icon' => 'heroicon-o-phone', 'collapsed' => false],
            'Suivi des dossiers' => ['icon' => 'heroicon-o-chart-bar', 'collapsed' => false],
            'Carnet d\'adresses' => ['icon' => 'heroicon-o-book-open', 'collapsed' => false],
            'Clients & Formations' => ['icon' => 'heroicon-o-academic-cap', 'collapsed' => false],
            'Contacts' => ['icon' => 'heroicon-o-users', 'collapsed' => false],
            'Configuration' => ['icon' => 'heroicon-o-adjustments-horizontal', 'collapsed' => false],
            'Communication' => ['icon' => 'heroicon-o-envelope', 'collapsed' => false],
            'Gestion documentaire' => ['icon' => 'heroicon-o-folder', 'collapsed' => false],
            'Recherche' => ['icon' => 'heroicon-o-magnifying-glass', 'collapsed' => false],
            'Paramètres' => ['icon' => 'heroicon-o-cog-6-tooth', 'collapsed' => true],
        ];
    }

    /**
     * Ordre par défaut : Activités, Suivi des dossiers, Carnet d'adresses
     * et Clients & Formations en tête (demande commune admin/télépro),
     * puis le reste.
     *
     * @return list<string>
     */
    public static function defaultNavigationGroupOrder(): array
    {
        return array_keys(static::navigationGroupDefinitions());
    }

    /**
     * Ne garde que les noms de groupes connus et complète avec ceux qui
     * manqueraient (nouveau groupe ajouté après coup, réglage périmé, ...).
     *
     * @param  array<int, mixed>  $order
     * @return list<string>
     */
    public static function sanitizeNavigationGroupOrder(array $order): array
    {
        $known = array_keys(static::navigationGroupDefinitions());

        $clean = array_values(array_intersect($order, $known));

        return array_merge($clean, array_diff($known, $clean));
    }

    /**
     * @return array<string, string>
     */
    public static function navigationGroupLabels(): array
    {
        return array_combine(
            array_keys(static::navigationGroupDefinitions()),
            array_keys(static::navigationGroupDefinitions()),
        );
    }

    /**
     * @return array<string, NavigationGroup>
     */
    public static function buildNavigationGroups(): array
    {
        $definitions = static::navigationGroupDefinitions();

        $order = static::sanitizeNavigationGroupOrder(
            app(CrmSettingsService::class)->get(
                static::NAVIGATION_ORDER_SETTING_KEY,
                static::defaultNavigationGroupOrder(),
            ) ?? static::defaultNavigationGroupOrder(),
        );

        // Important : le tableau doit être indexé par le libellé du groupe
        // (et non une simple liste 0..n), sinon l'algorithme de tri interne
        // de Filament (NavigationManager::get()) confond les clés
        // numériques avec les libellés et fait passer le premier groupe de
        // la liste au même rang que tous les groupes auto-détectés
        // (Configuration, Communication, ...), ce qui mélangeait l'ordre.
        return collect($order)
            ->mapWithKeys(fn (string $name) => [
                $name => NavigationGroup::make($name)
                    ->icon($definitions[$name]['icon'])
                    ->collapsed($definitions[$name]['collapsed']),
            ])
            ->all();
    }
}
