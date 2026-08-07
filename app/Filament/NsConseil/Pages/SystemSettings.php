<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\EnvSetting;
use App\Services\EnvSettingsService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class SystemSettings extends Page
{
    use \App\Filament\NsConseil\Concerns\HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Système';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Paramètres système';

    protected static string $view = 'filament.ns-conseil.pages.system-settings';

    /** Valeur courante du toggle (true = écrans d'erreur activés) */
    public bool $debugMode = false;

    public static function canAccess(array $parameters = []): bool
    {
        return static::userHasAnyRole(['admin']);
    }

    public function mount(): void
    {
        $setting = EnvSetting::firstWhere('key', 'APP_DEBUG');

        $this->debugMode = $setting
            ? in_array(strtolower($setting->value), ['true', '1'], true)
            : filter_var(config('app.debug'), FILTER_VALIDATE_BOOLEAN);
    }

    public function saveDebugMode(): void
    {
        $value = $this->debugMode ? 'true' : 'false';

        EnvSetting::updateOrCreate(
            ['key' => 'APP_DEBUG'],
            [
                'label'       => 'Mode debug (écrans d\'erreur)',
                'group'       => 'general',
                'description' => 'Affiche les écrans d\'erreur détaillés (Ignition). À désactiver en production.',
                'type'        => 'bool',
                'value'       => $value,
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order'  => 3,
            ]
        );

        app(EnvSettingsService::class)->syncToEnv();
        app(EnvSettingsService::class)->clearConfigCache();

        Notification::make()
            ->title($this->debugMode
                ? '⚠️ Écrans d\'erreur activés'
                : '✅ Écrans d\'erreur désactivés')
            ->body($this->debugMode
                ? 'Les détails d\'erreur sont maintenant visibles. Pensez à désactiver en production.'
                : 'Les erreurs sont maintenant masquées aux utilisateurs.')
            ->color($this->debugMode ? 'warning' : 'success')
            ->send();
    }
}
