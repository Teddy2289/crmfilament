<?php

namespace App\Filament\NsConseil\Resources\EmailResource\Pages;

use App\Filament\NsConseil\Resources\EmailResource;
use App\Models\Email;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CreateEmail extends CreateRecord
{
    protected static string $resource = EmailResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['type'] = Email::TYPE_SENT;
        $data['folder'] = Email::FOLDER_SENT;
        $data['sent_at'] = now();
        $data['message_id'] = uniqid('msg_').'_'.time().'@'.parse_url(config('app.url'), PHP_URL_HOST);

        return $data;
    }

    protected function afterCreate(): void
    {
        $email = $this->record;

        // Envoyer l'email via Laravel Mail
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

            // Marquer comme envoyé avec succès
            $email->update(['sent_at' => now()]);
        } catch (\Exception $e) {
            // En cas d'erreur, mettre dans les brouillons
            $email->update([
                'folder' => Email::FOLDER_DRAFTS,
                'type' => Email::TYPE_DRAFT,
            ]);

            throw $e;
        }
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('Envoyer')
                ->action('create')
                ->color('primary')
                ->icon('heroicon-o-paper-airplane'),

            Actions\Action::make('save_draft')
                ->label('Sauvegarder brouillon')
                ->action(function (array $data) {
                    $data['type'] = Email::TYPE_DRAFT;
                    $data['folder'] = Email::FOLDER_DRAFTS;
                    $data['user_id'] = Auth::id();
                    
                    $this->record = Email::create($data);
                    
                    $this->redirect($this->getResource()::getUrl('index'));
                })
                ->color('gray')
                ->icon('heroicon-o-document'),
        ];
    }
}
