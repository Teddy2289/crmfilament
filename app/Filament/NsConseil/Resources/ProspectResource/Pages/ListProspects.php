<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\ProspectResource\Actions\ImportProspectsAction;
use App\Models\Prospect;
use App\Traits\HasResponsiveTable;
use App\Traits\WithCommonEagerLoading;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProspects extends ListRecords
{
    use HasResponsiveTable, WithCommonEagerLoading;

    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouveau prospect'),
            ImportProspectsAction::make(),
        ];
    }

    /**
     * Définit la requête de base pour la table
     * Important pour les soft deletes et les scopes
     */
    protected function getTableQuery(): Builder
    {
        return $this->loadCommonProspectRelations(
            Prospect::query()->withoutTrashed()
        );
    }

    /**
     * Définit les onglets de filtrage
     */
    public function getTabs(): array
    {
        // Récupère la requête de base
        $baseQuery = $this->getTableQuery();

        // Onglet "Tous"
        $tabs = [
            'tous' => Tab::make('Tous')
                ->badge($baseQuery->count()),
        ];

        // Onglets pour chaque statut
        foreach (ProspectStatut::cases() as $statut) {
            $tabs[$statut->value] = Tab::make($statut->label())
                ->badge(
                    $baseQuery->clone()
                        ->where('statut', $statut->value)
                        ->count()
                )
                ->badgeColor($statut->color())
                ->modifyQueryUsing(
                    fn (Builder $query) => $query->where('statut', $statut->value)
                );
        }

        return $tabs;
    }
}
