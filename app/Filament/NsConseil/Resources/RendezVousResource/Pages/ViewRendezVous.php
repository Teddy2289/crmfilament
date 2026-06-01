<?php
// ── app/Filament/NsConseil/Resources/RendezVousResource/Pages/ViewRendezVous.php

namespace App\Filament\NsConseil\Resources\RendezVousResource\Pages;

use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Models\RendezVous;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRendezVous extends ViewRecord
{
    protected static string $resource = RendezVousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('sync_google')
                ->label('Sync Google Calendar')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn() => ! $this->record->google_event_id)
                ->action(function () {
                    app(\App\Services\GoogleCalendarService::class)->createEvent($this->record);
                    $this->refreshFormData(['google_event_id']);
                }),

            Actions\Action::make('voir_calendrier')
                ->label('Voir dans le calendrier')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->url('/ns-conseil/calendar'),

            Actions\DeleteAction::make(),
        ];
    }
}
