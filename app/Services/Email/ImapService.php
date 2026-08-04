<?php

namespace App\Services\Email;

use App\Models\Email;
use App\Models\EmailConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Message;

class ImapService
{
    protected Client $client;

    protected ?User $user;

    protected ?EmailConfiguration $config;

    public function __construct(?User $user = null, ?EmailConfiguration $config = null)
    {
        $this->user = $user ?? auth()->user();
        
        if (! $this->user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        $this->config = $config ?? $this->getEmailConfiguration();
        
        if (! $this->config) {
            throw new \Exception('Aucune configuration email trouvée pour cet utilisateur');
        }

        if (! class_exists(Client::class)) {
            throw new \Exception('Le paquet webklex/php-imap est requis pour synchroniser les emails.');
        }

        $this->connect();
    }

    protected function getEmailConfiguration(): ?EmailConfiguration
    {
        // Récupérer la configuration de l'utilisateur ou la configuration globale
        return EmailConfiguration::forUser($this->user->id)
            ->active()
            ->first();
    }

    /**
     * Synchronise les emails depuis le serveur IMAP
     */
    public function syncEmails(int $limit = null): array
    {
        $limit = $limit ?? $this->config->sync_limit;
        
        $stats = [
            'synced' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        try {
            $folder = $this->client->getFolder('INBOX');
            
            // Récupérer les messages non lus ou récents
            $messages = $folder->messages()
                ->unseen()
                ->limit($limit)
                ->get();

            foreach ($messages as $message) {
                try {
                    $this->processMessage($message);
                    $stats['synced']++;
                } catch (\Exception $e) {
                    Log::error('Erreur traitement message: '.$e->getMessage());
                    $stats['errors']++;
                }
            }

            // Marquer les messages comme lus sur le serveur
            $folder->messages()->setFlag(['Seen'], 'Seen');
            
            // Mettre à jour la dernière synchronisation
            $this->config->updateLastSync();

        } catch (\Exception $e) {
            Log::error('Erreur synchronisation emails: '.$e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Traite un message individuel
     */
    protected function processMessage(Message $message): void
    {
        // Vérifier si l'email existe déjà
        $existing = Email::where('message_id', $message->getMessageId())->first();
        
        if ($existing) {
            $stats['skipped']++;
            return;
        }

        // Télécharger les pièces jointes
        $attachments = $this->processAttachments($message);

        // Créer l'email en base
        Email::create([
            'type' => Email::TYPE_RECEIVED,
            'message_id' => $message->getMessageId(),
            'from_email' => $message->getFrom()[0]->mail ?? '',
            'from_name' => $message->getFrom()[0]->personal ?? '',
            'to_email' => $this->formatAddresses($message->getTo()),
            'cc_email' => $this->formatAddresses($message->getCc()),
            'bcc_email' => $this->formatAddresses($message->getBcc()),
            'subject' => $message->getSubject(),
            'body_text' => $message->getTextBody(),
            'body_html' => $message->getHTMLBody(),
            'attachments' => $attachments,
            'received_at' => $message->getDate(),
            'user_id' => $this->user->id,
            'folder' => Email::FOLDER_INBOX,
            'priority' => $this->detectPriority($message),
            'in_reply_to' => $message->getInReplyTo(),
        ]);
    }

    /**
     * Traite les pièces jointes d'un message
     */
    protected function processAttachments(Message $message): array
    {
        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            try {
                $filename = $attachment->getName();
                $content = $attachment->getContent();
                
                // Stocker dans storage/app/emails
                $path = 'emails/'.date('Y/m').'/'.uniqid().'_'.$filename;
                Storage::disk('local')->put($path, $content);

                $attachments[] = [
                    'filename' => $filename,
                    'path' => $path,
                    'size' => strlen($content),
                    'mime_type' => $attachment->getContentType(),
                ];
            } catch (\Exception $e) {
                Log::error('Erreur traitement pièce jointe: '.$e->getMessage());
            }
        }

        return $attachments;
    }

    /**
     * Formate les adresses email
     */
    protected function formatAddresses(array $addresses): string
    {
        return collect($addresses)
            ->map(fn ($addr) => $addr->mail)
            ->filter()
            ->implode(',');
    }

    /**
     * Détecte la priorité d'un email
     */
    protected function detectPriority(Message $message): string
    {
        $priority = $message->getPriority();
        
        // Priorité IMAP: 1 = haute, 3 = normale, 5 = basse
        return match (true) {
            $priority <= 2 => Email::PRIORITY_HIGH,
            $priority >= 4 => Email::PRIORITY_LOW,
            default => Email::PRIORITY_NORMAL,
        };
    }

    /**
     * Envoie un email via SMTP
     */
    public function sendEmail(array $data): bool
    {
        try {
            \Mail::raw($data['body_html'] ?? $data['body_text'], function ($message) use ($data) {
                $message->to(explode(',', $data['to_email']))
                    ->subject($data['subject'])
                    ->from($data['from_email'], $data['from_name'] ?? null);

                if (! empty($data['cc_email'])) {
                    $message->cc(explode(',', $data['cc_email']));
                }

                if (! empty($data['bcc_email'])) {
                    $message->bcc(explode(',', $data['bcc_email']));
                }

                if (! empty($data['body_html'])) {
                    $message->setBody($data['body_html'], 'text/html');
                }

                // Ajouter les pièces jointes
                if (! empty($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        $path = storage_path('app/'.$attachment['path']);
                        if (file_exists($path)) {
                            $message->attach($path, [
                                'as' => $attachment['filename'],
                                'mime' => $attachment['mime_type'],
                            ]);
                        }
                    }
                }
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur envoi email: '.$e->getMessage());
            return false;
        }
    }

    protected function connect(): void
    {
        $this->client = new Client($this->config->imap_connection_array);

        try {
            $this->client->connect();
        } catch (\Exception $e) {
            Log::error('Erreur de connexion IMAP: '.$e->getMessage());
            throw new \Exception('Impossible de se connecter au serveur email');
        }
    }

    /**
     * Déconnexion propre
     */
    public function __destruct()
    {
        try {
            if (isset($this->client) && $this->client->isConnected()) {
                $this->client->disconnect();
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de déconnexion
        }
    }
}
