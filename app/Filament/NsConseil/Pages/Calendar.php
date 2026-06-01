<?php

namespace App\Filament\NsConseil\Pages;

use App\Services\GoogleCalendarService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Calendar extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Calendrier';
    protected static ?string $navigationGroup = 'Activités';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.ns-conseil.pages.calendar';

    public bool $isGoogleConnected = false;

    public function mount(): void
    {
        $this->isGoogleConnected = app(GoogleCalendarService::class)
            ->isConnected(auth()->user());
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            // Bouton création RDV — toujours visible
            Action::make('new_rdv')
                ->label('Nouveau RDV')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action(fn() => redirect('/ns-conseil/rendez-vous/create')),
        ];

        if ($this->isGoogleConnected) {
            $actions[] = Action::make('sync_all')
                ->label('Resync RDV')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->action('syncAll');

            $actions[] = Action::make('disconnect_google')
                ->label('Déconnecter Google')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->outlined()
                ->action(fn() => redirect('/google/disconnect'));
        } else {
            $actions[] = Action::make('connect_google')
                ->label('Connecter Google Calendar')
                ->icon('heroicon-o-calendar-days')
                ->color('success')
                ->outlined()
                ->action(fn() => redirect('/google/redirect'));
        }

        return $actions;
    }

    public function syncAll(): void
    {
        $user    = auth()->user();
        $service = app(GoogleCalendarService::class);

        if (! $service->isConnected($user)) {
            Notification::make()->title('Non connecté à Google')->warning()->send();
            return;
        }

        $rdvs = \App\Models\RendezVous::query()
            ->where(fn($q) => $q->where('commercial_id', $user->id)
                ->orWhere('teleprospecteur_id', $user->id))
            ->whereIn('statut', ['planifie', 'decale'])
            ->whereNull('google_event_id')
            ->get();

        $count = 0;
        foreach ($rdvs as $rdv) {
            if ($service->createEvent($rdv)) $count++;
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
            \App\Filament\NsConseil\Widgets\CalendarWidget::class,
        ];
    }
}
