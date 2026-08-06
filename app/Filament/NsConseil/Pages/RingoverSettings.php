<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\EnvSetting;
use App\Services\EnvSettingsService;
use App\Services\RingoverService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RingoverSettings extends Page
{
    use \App\Filament\NsConseil\Concerns\HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Ringover';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Paramètres Ringover';

    protected static string $view = 'filament.ns-conseil.pages.ringover-settings';

    public int $ringoverTimeout = 10;

    public ?EnvSetting $ringoverTimeoutSetting = null;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (static::userHasAnyRole(['admin', 'superviseur'])) {
            return true;
        }

        return filled($user->ringover_user_id) || filled($user->ringover_email);
    }

    public function mount(): void
    {
        $this->loadRingoverTimeout();
    }

    protected function loadRingoverTimeout(): void
    {
        $this->ringoverTimeoutSetting = EnvSetting::firstWhere('key', 'RINGOVER_TIMEOUT');
        $this->ringoverTimeout = $this->ringoverTimeoutSetting
            ? (int) $this->ringoverTimeoutSetting->value
            : config('ringover.timeout', 10);
    }

    public function saveRingoverTimeout(): void
    {
        if (! $this->ringoverTimeoutSetting) {
            $this->ringoverTimeoutSetting = EnvSetting::create([
                'key' => 'RINGOVER_TIMEOUT',
                'label' => 'Timeout API Ringover',
                'group' => 'third_party',
                'description' => 'Délai d’attente maximum pour les requêtes API Ringover (secondes)',
                'type' => 'int',
                'value' => (string) $this->ringoverTimeout,
                'is_sensitive' => false,
                'is_editable' => true,
                'sort_order' => 64,
            ]);
        } else {
            $this->ringoverTimeoutSetting->update([
                'value' => (string) $this->ringoverTimeout,
            ]);
        }

        app(EnvSettingsService::class)->syncToEnv();

        Notification::make()
            ->title('Timeout Ringover mis à jour')
            ->body('La nouvelle valeur a été enregistrée et synchronisée dans le fichier .env.')
            ->success()
            ->send();
    }
}

