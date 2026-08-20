<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Pages;

use App\Filament\NsConseil\Resources\ClientResource;
use App\Filament\Widgets\HistoriqueModificationsWidget;
use App\Models\Client;
use App\Models\PipelineStatut;
use App\Filament\NsConseil\Resources\PartenaireResource;
use Filament\Actions;
use Filament\Forms;
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

            Actions\Action::make('voir_partenaire')
                ->label('Voir le partenaire')
                ->icon('heroicon-o-building-office-2')
                ->color('gray')
                ->visible(fn () => filled($this->record->partenaire_id))
                ->url(fn () => PartenaireResource::getUrl('view', ['record' => $this->record->partenaire_id]))
                ->openUrlInNewTab(),
            Actions\Action::make('changer_statut')
                ->label('Changer le statut')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('etat')
                        ->label('Nouveau statut client')
                        ->options(fn () => PipelineStatut::optionsFor('client') ?: Client::etatOptions())
                        ->default(fn () => $this->record->etat)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'etat' => $data['etat'],
                        'date_modification_statut' => now(),
                    ]);
                    $this->record->refresh();
                    $this->dispatch('notify', type: 'success', message: 'Statut client mis à jour.');
                }),
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
