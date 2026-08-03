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
