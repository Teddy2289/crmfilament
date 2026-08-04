<?php

// ── app/Filament/NsConseil/Resources/RendezVousResource/Pages/ViewRendezVous.php

namespace App\Filament\NsConseil\Resources\RendezVousResource\Pages;

use App\Filament\NsConseil\Resources\RendezVousResource;
use App\Services\GoogleCalendarService;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRendezVous extends ViewRecord
{
    protected static string $resource = RendezVousResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('lancer_appel_phoning')
                ->label('Lancer l\'appel')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->url(fn (): ?string => match ($this->record->rdvable_type) {
                    'App\Models\Prospect' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->rdvable_id,
                        'contact_type' => 'prospect',
                    ]),
                    'App\Models\Partenaire' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->rdvable_id,
                        'contact_type' => 'partenaire',
                    ]),
                    'App\Models\Client' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->rdvable_id,
                        'contact_type' => 'client',
                    ]),
                    default => null,
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->rdvable_type !== null && $this->record->rdvable_id !== null),

            Actions\Action::make('sync_google')
                ->label('Sync Google Calendar')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->visible(fn () => ! $this->record->google_event_id)
                ->action(function () {
                    app(GoogleCalendarService::class)->createEvent($this->record);
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
