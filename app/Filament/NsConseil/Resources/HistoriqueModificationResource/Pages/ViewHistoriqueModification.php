<?php

namespace App\Filament\NsConseil\Resources\HistoriqueModificationResource\Pages;

use App\Filament\NsConseil\Resources\HistoriqueModificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewHistoriqueModification extends ViewRecord
{
    protected static string $resource = HistoriqueModificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('voir_model')
                ->label('Voir l\'enregistrement')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(function ($record) {
                    $route = match ($record->model_type) {
                        'App\Models\Prospect' => 'filament.ns-conseil.resources.prospects.view',
                        'App\Models\Partenaire' => 'filament.ns-conseil.resources.partenaires.view',
                        'App\Models\Client' => 'filament.ns-conseil.resources.clients.view',
                        'App\Models\ContactPartenaire' => null, // Pas de page dédiée pour les contacts
                        default => null,
                    };

                    if (! $route) {
                        return null;
                    }

                    return route($route, ['record' => $record->model_id]);
                })
                ->openUrlInNewTab()
                ->visible(fn ($record) => in_array($record->model_type, [
                    'App\Models\Prospect',
                    'App\Models\Partenaire',
                    'App\Models\Client',
                ])),
        ];
    }
}
