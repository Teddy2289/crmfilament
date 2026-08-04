<?php

namespace App\Filament\NsConseil\Resources\EmailResource\Pages;

use App\Filament\NsConseil\Resources\EmailResource;
use App\Models\Email;
use App\Models\EmailConfiguration;
use App\Services\Email\ImapService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListEmails extends ListRecords
{
    protected static string $resource = EmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_mailbox')
                ->label('Synchroniser la boîte mail')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->outlined()
                ->action('syncMailbox'),

            Actions\CreateAction::make()->label('Nouvel email'),
        ];
    }

    /**
     * Synchronise manuellement la boîte mail de l'utilisateur connecté.
     */
    public function syncMailbox(): void
    {
        $user = Auth::user();
        $config = EmailConfiguration::query()
            ->forUser($user->id)
            ->active()
            ->first();

        if (! $config) {
            Notification::make()
                ->title('Aucune configuration email active')
                ->warning()
                ->body('Aucune configuration de boîte mail active n’a été trouvée pour cet utilisateur.')
                ->send();

            return;
        }

        try {
            $stats = (new ImapService($user, $config))->syncEmails();

            Notification::make()
                ->title('Synchronisation terminée')
                ->success()
                ->body("{$stats['synced']} email(s) synchronisé(s).")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur de synchronisation')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
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
