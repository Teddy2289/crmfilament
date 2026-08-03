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
        ];
    }
}
