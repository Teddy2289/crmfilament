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

    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
{
    $partenaire = Partenaire::find($recordId);
    $partenaire->changerStatut(OrganizationStatus::from($status));
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