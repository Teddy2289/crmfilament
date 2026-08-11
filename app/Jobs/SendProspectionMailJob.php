<?php

namespace App\Jobs;

use App\Filament\NsConseil\Resources\ProspectResource;
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
        public array $cc = [],
    ) {
    }

    public function handle(): void
    {
        try {
            $this->configureMailerFromEmailConfiguration();
            $cc = collect($this->cc)
                ->filter(fn (mixed $email): bool => is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL))
                ->map(fn (string $email): string => trim($email))
                ->reject(fn (string $email): bool => strcasecmp($email, $this->to) === 0)
                ->unique(fn (string $email): string => mb_strtolower($email))
                ->values()
                ->all();

            $pendingMail = Mail::mailer('smtp')->to($this->to);
            if ($cc !== []) {
                $pendingMail->cc($cc);
            }

            $pendingMail->send($this->mailable);
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
