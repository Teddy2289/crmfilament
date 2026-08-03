<?php

namespace App\Filament\NsConseil\Resources\EmailResource\Pages;

use App\Filament\NsConseil\Resources\EmailResource;
use App\Models\Email;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EditEmail extends EditRecord
{
    protected static string $resource = EmailResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si c'est un brouillon qu'on envoie
        if ($this->record->type === Email::TYPE_DRAFT && isset($data['type']) && $data['type'] === Email::TYPE_SENT) {
            $data['sent_at'] = now();
            $data['folder'] = Email::FOLDER_SENT;
            $data['message_id'] = $this->record->message_id ?: uniqid('msg_').'_'.time().'@'.parse_url(config('app.url'), PHP_URL_HOST);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $email = $this->record;

        // Si on passe de brouillon à envoyé
        if ($email->type === Email::TYPE_SENT && $email->wasRecentlyCreated === false && $email->wasChanged('type')) {
            try {
                Mail::raw($email->body_html ?: $email->body_text, function ($message) use ($email) {
                    $message->to(explode(',', $email->to_email))
                        ->subject($email->subject)
                        ->from($email->from_email, $email->from_name);

                    if ($email->cc_email) {
                        $message->cc(explode(',', $email->cc_email));
                    }

                    if ($email->bcc_email) {
                        $message->bcc(explode(',', $email->bcc_email));
                    }

                    if ($email->body_html) {
                        $message->setBody($email->body_html, 'text/html');
                    }
                });

                $email->update(['sent_at' => now()]);
            } catch (\Exception $e) {
                $email->update([
                    'folder' => Email::FOLDER_DRAFTS,
                    'type' => Email::TYPE_DRAFT,
                ]);

                throw $e;
            }
        }
    }

    protected function getFormActions(): array
    {
        $isDraft = $this->record->type === Email::TYPE_DRAFT;

        return [
            Actions\Action::make('save')
                ->label($isDraft ? 'Envoyer' : 'Sauvegarder')
                ->action('save')
                ->color('primary')
                ->icon($isDraft ? 'heroicon-o-paper-airplane' : 'heroicon-o-check'),

            Actions\Action::make('save_draft')
                ->label('Sauvegarder brouillon')
                ->visible($isDraft)
                ->action(function (array $data) {
                    $data['type'] = Email::TYPE_DRAFT;
                    $data['folder'] = Email::FOLDER_DRAFTS;
                    
                    $this->record->update($data);
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->color('gray')
                ->icon('heroicon-o-document'),
        ];
    }
}
