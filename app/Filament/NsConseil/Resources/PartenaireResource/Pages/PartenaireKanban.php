<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Pages;

use App\Enums\OrganizationStatus;
use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Models\Partenaire;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class PartenaireKanban extends KanbanBoard
{
    protected static string $resource = PartenaireResource::class;

    protected static string $model = Partenaire::class;

    protected static string $statusEnum = OrganizationStatus::class;

    protected static string $recordTitleAttribute = 'nom';

    protected static string $recordStatusAttribute = 'statut';

    protected static ?string $slug = 'ns-conseil/partenaires-kanban';

    protected static bool $shouldRegisterNavigation = false;

    /**
     * Nombre de cartes affichées par colonne avant "Charger plus" — sans ça,
     * une colonne à 500 partenaires oblige à scroller indéfiniment.
     */
    protected int $kanbanPageSize = 10;

    public array $visibleCounts = [];

    public function visibleCountFor(string|int $statusId): int
    {
        return $this->visibleCounts[$statusId] ?? $this->kanbanPageSize;
    }

    public function loadMore(string|int $statusId): void
    {
        $this->visibleCounts[$statusId] = $this->visibleCountFor($statusId) + $this->kanbanPageSize;
    }

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
{
    $partenaire = Partenaire::find($recordId);
    $partenaire->changerStatut(OrganizationStatus::from($status));
}

    /**
     * Charge le commercial en avance : la carte du Kanban affiche ses
     * initiales, sans ça ce serait une requête par carte affichée.
     */
    protected function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with('commercial');
    }

 protected function getHeaderActions(): array
{
    return [
        \Filament\Actions\Action::make('retour_liste')
            ->label('Vue liste')
            ->icon('heroicon-o-queue-list')
            ->color('gray')
            ->url(PartenaireResource::getUrl('index')),
    ];
}
}