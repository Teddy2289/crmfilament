<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Pages;

use App\Filament\NsConseil\Resources\ClientResource;
use App\Filament\Widgets\HistoriqueModificationsWidget;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            HistoriqueModificationsWidget::make([
                'modelType' => Client::class,
                'modelId' => $this->record->id,
            ]),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('lancer_appel_phoning')
                ->label('Lancer l\'appel')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-workflow', [
                    'contact_id' => $this->record->id,
                    'contact_type' => 'client',
                ]))
                ->openUrlInNewTab(),

            Actions\Action::make('toggle_contact')
                ->label(fn () => $this->record->ne_plus_contacter ? 'Réactiver' : 'Bloquer')
                ->icon(fn () => $this->record->ne_plus_contacter ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                ->color(fn () => $this->record->ne_plus_contacter ? 'success' : 'danger')
                ->action(function () {
                    if ($this->record->ne_plus_contacter) {
                        $this->record->reactiver();
                    } else {
                        $this->record->marquerNePlusContacter('Bloqué depuis la vue détail');
                    }
                }),
        ];
    }
}
