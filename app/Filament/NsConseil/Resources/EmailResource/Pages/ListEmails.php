<?php

namespace App\Filament\NsConseil\Resources\EmailResource\Pages;

use App\Filament\NsConseil\Resources\EmailResource;
use App\Models\Email;
use App\Models\EmailConfiguration;
use App\Services\Email\ImapService;
use App\Services\Email\MailboxSwitcherService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ListEmails extends ListRecords
{
    protected static string $resource = EmailResource::class;

    protected MailboxSwitcherService $mailboxSwitcherService;

    public function boot(): void
    {
        $this->mailboxSwitcherService = app(MailboxSwitcherService::class);
    }

    /**
     * Retourne le label de la boîte mail active.
     * Affiche "Aucune boîte mail configurée" si aucune config n'est disponible.
     */
    public function getActiveMailboxLabel(): string
    {
        $config = $this->mailboxSwitcherService->resolveActiveMailbox(Auth::id());

        if ($config === null) {
            return 'Aucune boîte mail configurée';
        }

        return $this->mailboxSwitcherService->buildOptionLabel($config);
    }

    /**
     * Retourne les options pour le composant Select (id => label).
     */
    public function getMailboxOptions(): array
    {
        return $this->mailboxSwitcherService
            ->getAvailableMailboxes(Auth::id())
            ->mapWithKeys(fn (EmailConfiguration $config) => [
                $config->id => $this->mailboxSwitcherService->buildOptionLabel($config),
            ])
            ->toArray();
    }

    /**
     * Retourne le nombre de boîtes mail disponibles pour l'utilisateur connecté.
     */
    public function getAvailableMailboxCount(): int
    {
        return $this->mailboxSwitcherService
            ->getAvailableMailboxes(Auth::id())
            ->count();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mailbox_switcher')
                ->label(fn () => $this->getActiveMailboxLabel())
                ->icon('heroicon-o-inbox')
                ->disabled(fn () => $this->getAvailableMailboxCount() <= 1)
                ->form([
                    Forms\Components\Select::make('mailbox_id')
                        ->label('Boîte mail active')
                        ->options(fn () => $this->getMailboxOptions())
                        ->default(fn () => session('active_mailbox_id'))
                        ->allowHtml()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $mailboxId = $data['mailbox_id'] ?? $this->mailboxSwitcherService->resolveActiveMailbox(Auth::id())?->id;

                    if ($mailboxId === null) {
                        Notification::make()
                            ->title('Aucune boîte mail sélectionnée')
                            ->warning()
                            ->body('Veuillez sélectionner une boîte mail avant de changer la boîte active.')
                            ->send();

                        return;
                    }

                    app(MailboxSwitcherService::class)->switchMailbox((int) $mailboxId);
                    $this->resetPage();
                }),

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
        $config = $this->mailboxSwitcherService->resolveActiveMailbox($user->id);

        if ($config === null) {
            Notification::make()
                ->title('Aucune boîte mail active')
                ->warning()
                ->body('Aucune boîte mail active n\'est configurée. Veuillez sélectionner une boîte mail.')
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
        } catch (\Throwable $e) {
            Log::error('Erreur de synchronisation IMAP : ' . $e->getMessage(), [
                'exception' => $e,
                'config_id' => $config->id,
            ]);

            Notification::make()
                ->title('Erreur de synchronisation')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Définit la requête de base pour la table, filtrée selon la boîte active.
     *
     * - Config globale (is_global = true)  : filtre sur from_email = config->email
     * - Config personnelle (is_global = false) : filtre sur user_id = auth()->id()
     * - Aucune config (null) : retourne une requête vide (1 = 0)
     */
    protected function getTableQuery(): Builder
    {
        $config = $this->mailboxSwitcherService->resolveActiveMailbox(Auth::id());

        if ($config === null) {
            return Email::query()->whereRaw('1 = 0');
        }

        if ($config->is_global) {
            return Email::query()->where('from_email', $config->email);
        }

        return Email::query()->where('user_id', Auth::id());
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
