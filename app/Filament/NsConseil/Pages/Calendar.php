<?php

namespace App\Filament\NsConseil\Pages;

use App\Filament\NsConseil\Widgets\CalendarWidget;
use App\Models\RendezVous;
use App\Services\GoogleCalendarService;
use App\Support\AccessRightsCatalog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Calendrier';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.ns-conseil.pages.calendar';

    public static function canAccess(): bool
    {
        return AccessRightsCatalog::userCan(auth()->user(), 'calendrier.view');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public bool $isGoogleConnected = false;
    public array $googleCalendars = [];

    public function mount(): void
    {
        $service = app(GoogleCalendarService::class);
        $user = auth()->user();
        $this->isGoogleConnected = $service->isConnected($user);
        $this->googleCalendars = $this->isGoogleConnected
            ? $service->getCalendars($user)
            : [];
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            // Bouton création RDV — toujours visible et mis en avant
            Action::make('new_rdv')
                ->label('Nouveau RDV')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->size('md')
                ->action(fn () => redirect('/ns-conseil/rendez-vous/create')),
        ];

        if ($this->isGoogleConnected) {
            $actions[] = Action::make('sync_all')
                ->label('Synchroniser')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->outlined()
                ->size('sm')
                ->tooltip('Synchroniser les RDV avec Google Calendar')
                ->action('syncAll');

            $actions[] = Action::make('disconnect_google')
                ->label('Déconnecter')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->outlined()
                ->size('sm')
                ->tooltip('Déconnecter Google Calendar')
                ->action(fn () => redirect('/google/disconnect'));
        } else {
            $actions[] = Action::make('connect_google')
                ->label('Connecter Google')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->outlined()
                ->size('sm')
                ->tooltip('Connecter votre compte Google Calendar')
                ->action(fn () => redirect('/google/redirect'));
        }

        return $actions;
    }

    public function syncAll(): void
    {
        $user = auth()->user();
        $service = app(GoogleCalendarService::class);

        if (! $service->isConnected($user)) {
            Notification::make()->title('Non connecté à Google')->warning()->send();

            return;
        }

        $rdvs = RendezVous::query()
            ->where(fn ($q) => $q->where('commercial_id', $user->id)
                ->orWhere('teleprospecteur_id', $user->id))
            ->whereIn('statut', ['planifie', 'decale'])
            ->whereNull('google_event_id')
            ->get();

        $count = 0;
        foreach ($rdvs as $rdv) {
            if ($service->createEvent($rdv)) {
                $count++;
            }
        }

        Notification::make()
            ->title("{$count} RDV synchronisés avec Google Calendar")
            ->success()
            ->send();
    }

    // ✅ Filament appelle automatiquement getFooterWidgets() via son layout
    // Le blade NE doit PAS les appeler manuellement
    protected function getFooterWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
