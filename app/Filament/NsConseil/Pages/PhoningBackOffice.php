<?php

namespace App\Filament\NsConseil\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Concerns\HasQueueManagement;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmSettingsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PhoningBackOffice extends Page
{
       use \App\Filament\NsConseil\Concerns\HasRoleAccess;
    use HasQueueManagement;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'File d\'appels — Back-office';

    protected static ?string $title = 'File d\'appels — Back-office';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 3;

    public static function canAccess(array $parameters = []): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur']);
    }

       public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected static string $view = 'filament.ns-conseil.pages.phoning-back-office';

    public ?int $selectedUserId = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_phoning')
                ->label('→ Parcours d\'appels')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('success')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-workflow')),

            Action::make('reset_order')
                ->label('Réinitialiser l\'ordre')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Réinitialiser l\'ordre ?')
                ->modalDescription('L\'ordre par défaut (par statut et rappel) sera restauré.')
                ->action(fn () => $this->resetOrder()),
        ];
    }
}

