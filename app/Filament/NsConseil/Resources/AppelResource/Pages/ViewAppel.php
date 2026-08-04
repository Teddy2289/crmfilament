<?php

namespace App\Filament\NsConseil\Resources\AppelResource\Pages;

use App\Filament\NsConseil\Resources\AppelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAppel extends ViewRecord
{
    protected static string $resource = AppelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voir_fiche')
                ->label('Voir la fiche')
                ->icon('heroicon-o-user')
                ->url(fn (): ?string => match ($this->record->appelable_type) {
                    'App\Models\Prospect' => route('filament.ns-conseil.resources.prospects.view', $this->record->appelable_id),
                    'App\Models\Partenaire' => route('filament.ns-conseil.resources.partenaires.view', $this->record->appelable_id),
                    'App\Models\Client' => route('filament.ns-conseil.resources.clients.view', $this->record->appelable_id),
                    'App\Models\Opportunite' => route('filament.ns-conseil.resources.opportunites.view', $this->record->appelable_id),
                    default => null,
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->appelable_type !== null && $this->record->appelable_id !== null),

            Actions\Action::make('lancer_appel_phoning')
                ->label('Lancer l\'appel')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->url(fn (): ?string => match ($this->record->appelable_type) {
                    'App\Models\Prospect' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->appelable_id,
                        'contact_type' => 'prospect',
                    ]),
                    'App\Models\Partenaire' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->appelable_id,
                        'contact_type' => 'partenaire',
                    ]),
                    'App\Models\Client' => route('filament.ns-conseil.pages.phoning-workflow', [
                        'contact_id' => $this->record->appelable_id,
                        'contact_type' => 'client',
                    ]),
                    default => null,
                })
                ->openUrlInNewTab()
                ->visible(fn (): bool => $this->record->appelable_type !== null && $this->record->appelable_id !== null),
        ];
    }
}
