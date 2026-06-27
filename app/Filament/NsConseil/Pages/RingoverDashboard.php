<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\RingoverAppelsRecents;
use App\Filament\NsConseil\Widgets\RingoverStatsOverview;
use App\Services\RingoverService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class RingoverDashboard extends Page
{
    use \App\Filament\NsConseil\Concerns\HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Dashboard Ringover';

    protected static ?string $navigationGroup = 'ActivitÃ©s';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Dashboard Ringover';

    protected static string $view = 'filament.ns-conseil.pages.ringover-dashboard';

    public bool $connexionOk = false;

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur']);
    }

    public function mount(): void
    {
        $this->connexionOk = app(RingoverService::class)->testConnection();
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
                }),
        ];
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
