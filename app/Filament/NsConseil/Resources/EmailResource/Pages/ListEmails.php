<?php

namespace App\Filament\NsConseil\Resources\EmailResource\Pages;

use App\Filament\NsConseil\Resources\EmailResource;
use App\Models\Email;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEmails extends ListRecords
{
    protected static string $resource = EmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvel email'),
        ];
    }

    /**
     * Définit la requête de base pour la table
     */
    protected function getTableQuery(): Builder
    {
        return Email::query()->where('user_id', auth()->id());
    }

    /**
     * Définit les onglets de filtrage par dossier
     */
    public function getTabs(): array
    {
        $baseQuery = $this->getTableQuery();

        return [
            'tous' => Tab::make('Tous')
                ->badge($baseQuery->count()),

            'inbox' => Tab::make('Boîte de réception')
                ->badge($baseQuery->clone()->inbox()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->inbox()),

            'sent' => Tab::make('Envoyés')
                ->badge($baseQuery->clone()->sentFolder()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->sentFolder()),

            'drafts' => Tab::make('Brouillons')
                ->badge($baseQuery->clone()->draftsFolder()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->draftsFolder()),

            'trash' => Tab::make('Corbeille')
                ->badge($baseQuery->clone()->trash()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->trash()),

            'archive' => Tab::make('Archives')
                ->badge($baseQuery->clone()->archive()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->archive()),
        ];
    }
}
