<?php

namespace App\Filament\NsConseil\Resources\ActiviteVenteResource\Pages;

use App\Filament\NsConseil\Resources\ActiviteVenteResource;
use App\Models\ActiviteVente;
use App\Models\Partenaire;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActiviteVentes extends ListRecords
{
    protected static string $resource = ActiviteVenteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('recalculer_toutes')
                ->label('Recalculer')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Recalculer les ventes')
                ->modalDescription('Les statistiques seront recalculées depuis les clients et dossiers de formation liés aux partenaires.')
                ->action(function (): void {
                    Partenaire::query()
                        ->whereHas('clients')
                        ->each(fn (Partenaire $partenaire) => ActiviteVente::actualiserPourPartenaire($partenaire->id));
                })
                ->successNotificationTitle('Activités vente recalculées'),

            CreateAction::make(),
        ];
    }
}
