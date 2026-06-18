<?php

namespace App\Filament\Allopro\Resources\AffaireInterventionResource\Pages;

use App\Filament\Allopro\Resources\AffaireInterventionResource;
use App\Models\AffaireIntervention;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListAffaireInterventions extends ListRecords
{
    protected static string $resource = AffaireInterventionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle affaire')
                ->icon('heroicon-o-plus')
                ->visible(fn () => auth()->user()?->hasAnyRole(['operateur_n1', 'responsable_plateau'])),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(AffaireIntervention::count()),

            'en_attente' => Tab::make('En attente')
                ->badge(AffaireIntervention::enAttente()->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($q) => $q->enAttente()),

            'confirmees' => Tab::make('Confirmées')
                ->badge(AffaireIntervention::confirmees()->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn ($q) => $q->confirmees()),

            'en_cours' => Tab::make('En cours')
                ->badge(AffaireIntervention::enCours()->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn ($q) => $q->enCours()),

            'realisees' => Tab::make('Réalisées')
                ->badge(AffaireIntervention::realisees()->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn ($q) => $q->realisees()),

            'echec' => Tab::make('Échecs & Annulations')
                ->badge(AffaireIntervention::enEchec()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($q) => $q->enEchec()),

            'du_jour' => Tab::make("Aujourd'hui")
                ->badge(AffaireIntervention::duJour()->count())
                ->modifyQueryUsing(fn ($q) => $q->duJour()),
        ];
    }
}
