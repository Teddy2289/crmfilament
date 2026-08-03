<?php

namespace App\Filament\NsConseil\Resources\EmailResource\Pages;

use App\Filament\NsConseil\Resources\EmailResource;
use App\Models\Email;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;

class ViewEmail extends ViewRecord
{
    protected static string $resource = EmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Répondre')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('primary')
                ->action(function () {
                    $this->redirect($this->getResource()::getUrl('create', [
                        'in_reply_to' => $this->record->message_id,
                        'to_email' => $this->record->from_email,
                        'subject' => 'Re: '.$this->record->subject,
                    ]));
                }),

            Actions\Action::make('reply_all')
                ->label('Répondre à tous')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('primary')
                ->action(function () {
                    $recipients = array_filter([
                        $this->record->from_email,
                        $this->record->cc_email,
                    ]);

                    $this->redirect($this->getResource()::getUrl('create', [
                        'in_reply_to' => $this->record->message_id,
                        'to_email' => implode(',', $recipients),
                        'cc_email' => $this->record->to_email,
                        'subject' => 'Re: '.$this->record->subject,
                    ]));
                }),

            Actions\Action::make('forward')
                ->label('Transférer')
                ->icon('heroicon-o-arrow-right')
                ->color('gray')
                ->action(function () {
                    $this->redirect($this->getResource()::getUrl('create', [
                        'body_html' => '<br><br><br>---------- Message transféré ----------<br>'.$this->record->body_html,
                        'subject' => 'Fwd: '.$this->record->subject,
                    ]));
                }),

            Actions\Action::make('mark_as_read')
                ->label('Marquer comme lu')
                ->icon('heroicon-o-envelope-open')
                ->color('success')
                ->visible(fn () => ! $this->record->is_read)
                ->action(function () {
                    $this->record->markAsRead();
                    Notification::make()
                        ->title('Email marqué comme lu')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('mark_as_unread')
                ->label('Marquer comme non lu')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->visible(fn () => $this->record->is_read)
                ->action(function () {
                    $this->record->markAsUnread();
                    Notification::make()
                        ->title('Email marqué comme non lu')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('archive')
                ->label('Archiver')
                ->icon('heroicon-o-archive-box')
                ->color('gray')
                ->visible(fn () => $this->record->folder !== Email::FOLDER_ARCHIVE)
                ->action(function () {
                    $this->record->archive();
                    Notification::make()
                        ->title('Email archivé')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('move_to_trash')
                ->label('Corbeille')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => $this->record->folder !== Email::FOLDER_TRASH)
                ->action(function () {
                    $this->record->moveToTrash();
                    Notification::make()
                        ->title('Email déplacé vers la corbeille')
                        ->success()
                        ->send();
                }),

            Actions\Action::make('restore')
                ->label('Restaurer')
                ->icon('heroicon-o-arrow-uturn-up')
                ->color('success')
                ->visible(fn () => $this->record->folder === Email::FOLDER_TRASH)
                ->action(function () {
                    $this->record->restore();
                    Notification::make()
                        ->title('Email restauré')
                        ->success()
                        ->send();
                }),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Marquer comme lu lors de la consultation
        if (! $this->record->is_read) {
            $this->record->markAsRead();
        }

        return $data;
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }
}
