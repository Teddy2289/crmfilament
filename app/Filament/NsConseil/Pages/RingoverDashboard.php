<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\RingoverAppelsRecents;
use App\Filament\NsConseil\Widgets\RingoverStatsOverview;
use App\Models\EnvSetting;
use App\Services\EnvSettingsService;
use App\Services\RingoverTagService;
use App\Services\RingoverService;
use App\Services\RingoverUserMapper;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RingoverDashboard extends Page
{
    use \App\Filament\NsConseil\Concerns\HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Tableau de bord Ringover';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Tableau de bord Ringover';

    protected static string $view = 'filament.ns-conseil.pages.ringover-dashboard';

    public bool $connexionOk = false;

    public array $diagnostic = [];

    public ?EnvSetting $ringoverTimeoutSetting = null;

    public int $ringoverTimeout = 10;

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
        $this->connexionOk = app(RingoverService::class)->testConnection();
        $this->refreshDiagnostic();
        $this->loadRingoverTimeout();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    \Cache::flush();
                    \Artisan::call('ringover:sync', [
                        '--pages' => 3,
                        '--per-page' => 50,
                        '--from' => now()->subDay()->timestamp,
                    ]);

                    Notification::make()
                        ->title('Synchronisation terminee')
                        ->body(\Artisan::output())
                        ->success()
                        ->send();

                    $this->refreshDiagnostic();
                }),
            Action::make('sync_users')
                ->label('Mapper utilisateurs')
                ->icon('heroicon-o-user-group')
                ->action(function () {
                    $ringoverUsers = app(RingoverService::class)->getUsers();
                    $result = app(RingoverUserMapper::class)->syncFromRingoverUsers($ringoverUsers);

                    $this->refreshDiagnostic();

                    Notification::make()
                        ->title('Correspondance utilisateurs Ringover terminée')
                        ->body("Mappés : {$result['mapped']} | Mis à jour : {$result['updated']} | Non trouvés : {$result['unmatched']}")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function refreshDiagnostic(): void
    {
        $this->diagnostic = app(RingoverTagService::class)->diagnostic();
    }

    protected function loadRingoverTimeout(): void
    {
        $this->ringoverTimeoutSetting = EnvSetting::firstWhere('key', 'RINGOVER_TIMEOUT');
        $this->ringoverTimeout = $this->ringoverTimeoutSetting ? (int) $this->ringoverTimeoutSetting->value : config('ringover.timeout', 10);
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
            $this->ringoverTimeoutSetting->update(['value' => (string) $this->ringoverTimeout]);
        }

        app(EnvSettingsService::class)->syncToEnv();

        Notification::make()
            ->title('Timeout Ringover mis à jour')
            ->body('La nouvelle valeur a été enregistrée et synchronisée.')
            ->success()
            ->send();
    }

    protected function getHeaderWidgets(): array
    {
        return [RingoverStatsOverview::class];
    }

    protected function getFooterWidgets(): array
    {
        return [RingoverAppelsRecents::class];
    }
}

