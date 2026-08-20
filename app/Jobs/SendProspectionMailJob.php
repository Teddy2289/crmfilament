<?php

namespace App\Jobs;

use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\Email;
use App\Models\EmailConfiguration;
use App\Models\Prospect;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Envoie un mail de prospection en tâche de fond et notifie (cloche Filament)
 * le téléprospecteur à l'origine de l'appel du résultat réel — succès ou
 * échec avec le détail de l'erreur — puisque Mail::queue() seul ne donne
 * aucune visibilité sur l'issue de l'envoi une fois sorti de la requête HTTP.
 */
class SendProspectionMailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Mailable $mailable,
        public string $to,
        public string $emailLabel,
        public ?int $prospectId = null,
        public ?int $notifyUserId = null,
        public ?string $sourceEmail = null,
        public ?int $emailConfigurationId = null,
        public ?string $cc = null,
        public ?string $bcc = null,
    ) {
    }

    public function handle(): void
    {
        try {
            // Le transport SMTP Laravel provient du .env. La configuration CRM
            // peut rester informative, mais ne doit pas écraser les identifiants valides.
            $pendingMail = Mail::mailer('smtp')->to($this->to);
            if (is_string($this->cc) && filter_var(trim($this->cc), FILTER_VALIDATE_EMAIL)
                && strcasecmp(trim($this->cc), $this->to) !== 0) {
                $pendingMail->cc(trim($this->cc));
            }
            if (is_string($this->bcc) && filter_var(trim($this->bcc), FILTER_VALIDATE_EMAIL)
                && strcasecmp(trim($this->bcc), $this->to) !== 0) {
                $pendingMail->bcc(trim($this->bcc));
            }
            $pendingMail->send($this->mailable);

            // Conserver une copie dans la boîte Envoyés du CRM.
            Email::create([
                'type' => Email::TYPE_SENT,
                'folder' => Email::FOLDER_SENT,
                'from_email' => $this->sourceEmail ?: config('mail.from.address'),
                'from_name' => config('mail.from.name'),
                'to_email' => $this->to,
                'cc_email' => $this->cc,
                'bcc_email' => $this->bcc,
                'subject' => $this->emailLabel,
                'body_text' => 'Message envoyé depuis le CRM : '.$this->emailLabel,
                'sent_at' => now(),
                'user_id' => $this->notifyUserId,
                'priority' => Email::PRIORITY_NORMAL,
            ]);

            $this->notifier(true);
        } catch (\Throwable $e) {
            Log::error("SendProspectionMailJob: échec envoi [{$this->emailLabel}] à {$this->to}" .
                ($this->prospectId ? " (prospect #{$this->prospectId})" : '') . " : " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $this->notifier(false, $e->getMessage());
        }
    }

    protected function notifier(bool $success, ?string $error = null): void
    {
        if (! $this->notifyUserId) {
            return;
        }

        $user = User::find($this->notifyUserId);
        if (! $user) {
            return;
        }

        $prospect = $this->prospectId ? Prospect::find($this->prospectId) : null;

        $lignes = [
            'Destinataire' => $this->to,
        ];
        if ($this->sourceEmail) {
            $lignes['Configuration email'] = $this->sourceEmail;
        }
        if ($prospect) {
            $lignes['Prospect'] = $prospect->nom;
        }
        if (! $success) {
            $lignes['Erreur'] = Str::limit($error ?? 'Inconnue', 300);
        }

        $notification = Notification::make()
            ->title($success
                ? "Email envoyé : {$this->emailLabel}"
                : "Échec d'envoi email : {$this->emailLabel}")
            ->body($this->formatBody($lignes))
            ->icon($success ? 'heroicon-o-envelope-open' : 'heroicon-o-exclamation-triangle');

        $success ? $notification->success() : $notification->danger();

        if ($prospect) {
            $notification->actions([
                Action::make('voir')
                    ->label('Voir la fiche')
                    ->url(ProspectResource::getUrl('view', ['record' => $prospect->id], panel: 'ns-conseil')),
            ]);
        }

        $notification->sendToDatabase($user);
    }

    /**
     * @param array<string, string> $lignes
     */
    protected function formatBody(array $lignes): string
    {
        $rows = collect($lignes)
            ->map(fn (string $valeur, string $label) => sprintf(
                '<div style="margin-bottom:2px;"><strong>%s :</strong> %s</div>',
                e($label),
                e($valeur),
            ))
            ->implode('');

        return "<div>{$rows}</div>";
    }

    protected function configureMailerFromEmailConfiguration(): void
    {
        if (! $this->emailConfigurationId) {
            return;
        }

        $config = EmailConfiguration::find($this->emailConfigurationId);
        if (! $config) {
            return;
        }

        $smtpConfig = [
            'transport' => 'smtp',
            'host' => $config->smtp_host,
            'port' => $config->smtp_port,
            'username' => $config->email,
            'password' => $config->password,
            'encryption' => $config->smtp_encryption === 'none' ? null : $config->smtp_encryption,
            'timeout' => null,
            'local_domain' => config('mail.mailers.smtp.local_domain'),
        ];

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => array_merge(config('mail.mailers.smtp', []), $smtpConfig),
            'mail.from.address' => $config->email,
            'mail.from.name' => $config->from_name ?: config('mail.from.name'),
        ]);
    }
}
