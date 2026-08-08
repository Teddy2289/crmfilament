<?php

namespace App\Filament\NsConseil\Concerns;

use App\Jobs\SendProspectionMailJob;
use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\ContactSansCSEMail;
use App\Mail\GenericProspectionMail;
use App\Mail\PreviewableProspectionMail;
use App\Mail\PriseContactBlocMail;
use App\Models\EmailTemplate;
use App\Models\Prospect;
use App\Models\RendezVous;
use Filament\Notifications\Notification;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;

/**
 * Concern : aperçu et confirmation d'envoi des emails de prospection.
 * Requirements 4.1–4.13, 13.2, 14.3
 */
trait HasEmailPreview
{
    // ── Propriétés Livewire ──────────────────────────────────────────

    public bool    $showEmailPreview              = false;
    public bool    $emailPreviewConfirmed         = false;
    public ?string $emailPreviewRecipient         = null;
    public ?string $emailPreviewSubject           = null;
    public ?string $emailPreviewBody              = null;
    public ?string $emailPreviewOriginalSubject   = null;
    public ?string $emailPreviewOriginalBody      = null;

    public string $emailPreviewMode               = 'status';
    public string $emailTemplateKey               = '';
    public string $emailTabSubject                = '';
    public string $emailTabBody                   = '';
    public ?string $emailTabRecipient             = null;

    // ── Actions publiques ────────────────────────────────────────────

    /** Req 4.4 */
    public function confirmEmailPreview(): void
    {
        if (blank($this->emailPreviewSubject) || blank(strip_tags((string) $this->emailPreviewBody))) {
            Notification::make()
                ->title('Mail incomplet')
                ->body('Le sujet et le corps du message sont obligatoires avant envoi.')
                ->danger()
                ->send();

            return;
        }

        // Req 4.7 : emailPreviewConfirmed = true ⟹ showEmailPreview = false
        $this->emailPreviewConfirmed = true;
        $this->showEmailPreview      = false;

        if ($this->emailPreviewMode === 'standalone') {
            $this->sendStandaloneEmail();
            $this->emailPreviewMode = 'status';
            $this->resetEmailPreviewState();

            return;
        }

        $this->submitResult();
    }

    /** Req 4.5 */
    public function cancelEmailPreview(): void
    {
        $this->showEmailPreview      = false;
        $this->emailPreviewConfirmed = false;
        $this->emailPreviewMode      = 'status';
    }

    public function openEmailPreviewStandalone(): void
    {
        if (blank($this->emailTabRecipient) || ! filter_var(trim($this->emailTabRecipient), FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->title('Destinataire invalide')
                ->body('Veuillez renseigner une adresse email valide.')
                ->danger()
                ->send();

            return;
        }

        if (blank($this->emailTabSubject) || blank(strip_tags((string) $this->emailTabBody))) {
            Notification::make()
                ->title('Mail incomplet')
                ->body('Le sujet et le corps du message sont obligatoires avant envoi.')
                ->danger()
                ->send();

            return;
        }

        $this->emailPreviewMode            = 'standalone';
        $this->showEmailPreview            = true;
        $this->emailPreviewRecipient       = trim($this->emailTabRecipient);
        $this->emailPreviewSubject         = trim($this->emailTabSubject);
        $this->emailPreviewBody            = $this->emailTabBody;
        $this->emailPreviewOriginalSubject = $this->emailTabSubject;
        $this->emailPreviewOriginalBody    = $this->emailTabBody;
        $this->emailPreviewConfirmed       = false;
    }

    public function loadEmailTemplate(): void
    {
        if (blank($this->emailTemplateKey)) {
            return;
        }

        $template = EmailTemplate::findByCle($this->emailTemplateKey);
        if (! $template) {
            return;
        }

        $variables = $this->buildEmailTemplateVariables();

        $this->emailTabSubject   = $template->renderSujet($variables);
        $this->emailTabBody      = $template->renderCorps($variables);
        $this->emailTabRecipient = $this->emailTabRecipient ?? $this->resolveStandaloneEmailRecipient();
    }

    protected function buildEmailTemplateVariables(): array
    {
        $data = [
            'nom'                   => $this->currentContactData['nom'] ?? null,
            'entreprise_nom'        => $this->currentContactData['nom'] ?? null,
            'telephone'             => $this->currentContactData['telephone'] ?? null,
            'ville'                 => $this->currentContactData['ville'] ?? null,
            'code_postal'           => $this->currentContactData['code_postal'] ?? null,
            'departement'           => $this->currentContactData['departement'] ?? null,
            'email'                 => $this->currentContactData['email'] ?? null,
            'interlocuteur_nom'     => $this->currentContactData['interlocuteur_nom'] ?? null,
            'interlocuteur_prenom'  => $this->currentContactData['interlocuteur_prenom'] ?? null,
            'interlocuteur_email'   => $this->currentContactData['interlocuteur_email'] ?? null,
            'interlocuteur_telephone' => $this->currentContactData['interlocuteur_telephone'] ?? null,
            'email_general_standard' => $this->currentContactData['email_general_standard'] ?? null,
            'secteur_activite'      => $this->currentContactData['secteur_activite'] ?? null,
        ];

        if ($this->currentContact instanceof Prospect) {
            $data = array_merge($data, [
                'nom'                   => $this->currentContact->nom,
                'entreprise_nom'        => $this->currentContact->nom,
                'telephone'             => $this->currentContact->telephone,
                'ville'                 => $this->currentContact->ville,
                'code_postal'           => $this->currentContact->code_postal,
                'departement'           => $this->currentContact->departement,
                'email'                 => $this->currentContact->email,
                'interlocuteur_nom'     => $this->currentContact->interlocuteur_nom,
                'interlocuteur_prenom'  => $this->currentContact->interlocuteur_prenom,
                'interlocuteur_email'   => $this->currentContact->interlocuteur_email,
                'interlocuteur_telephone' => $this->currentContact->interlocuteur_telephone,
                'email_general_standard' => $this->currentContact->email_general_standard,
                'secteur_activite'      => $this->currentContact->secteur_activite,
            ]);
        } elseif (is_array($this->currentContact)) {
            $data = array_merge($data, array_filter($this->currentContact, fn ($value, $key) => is_string($value) && in_array($key, [
                'nom', 'telephone', 'ville', 'code_postal', 'departement', 'email',
                'interlocuteur_nom', 'interlocuteur_prenom', 'interlocuteur_email', 'interlocuteur_telephone',
                'email_general_standard', 'secteur_activite',
            ], true), ARRAY_FILTER_USE_BOTH));
        }

        return array_filter($data, fn ($value) => $value !== null);
    }

    protected function resolveStandaloneEmailRecipient(): ?string
    {
        $candidates = [];

        if ($this->currentContact instanceof Prospect) {
            $candidates = [
                $this->currentContact->interlocuteur_email,
                $this->currentContact->email_general_standard,
                $this->currentContact->email,
            ];
        } elseif (is_array($this->currentContact)) {
            $candidates = [
                $this->currentContact['interlocuteur_email'] ?? null,
                $this->currentContact['email_general_standard'] ?? null,
                $this->currentContact['email'] ?? null,
            ];
        }

        foreach ($candidates as $candidate) {
            if (! empty($candidate) && filter_var(trim((string) $candidate), FILTER_VALIDATE_EMAIL)) {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    protected function resolveProspectIdForStandaloneEmail(): ?int
    {
        if ($this->currentContact instanceof Prospect) {
            return $this->currentContact->getKey();
        }

        if (is_array($this->currentContact) && isset($this->currentContact['id'])) {
            return (int) $this->currentContact['id'];
        }

        return null;
    }

    protected function sendStandaloneEmail(): void
    {
        $recipient = trim((string) ($this->emailPreviewRecipient ?? ''));
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            Notification::make()
                ->title('Destinataire invalide')
                ->body('Adresse email invalide ou manquante.')
                ->danger()
                ->send();

            return;
        }

        $subject = trim((string) $this->emailPreviewSubject);
        $body = $this->emailPreviewBody ?? '';

        if (blank($subject) || blank(strip_tags($body))) {
            Notification::make()
                ->title('Mail incomplet')
                ->body('Le sujet et le corps du message sont obligatoires.')
                ->danger()
                ->send();

            return;
        }

        try {
            dispatch(new SendProspectionMailJob(
                mailable: new PreviewableProspectionMail($subject, $body),
                to: $recipient,
                emailLabel: 'Email personnalisé',
                prospectId: $this->resolveProspectIdForStandaloneEmail(),
                notifyUserId: Auth::id(),
            ));

            Notification::make()
                ->title('Email en file d’attente')
                ->body("Le message a été mis en file d'attente pour {$recipient}.")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Échec d’envoi')
                ->body('Impossible de mettre le message en file : ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /** Req 4.6 */
    public function syncEmailPreviewContent(string $subject, string $body, ?string $recipient = null): void
    {
        $this->emailPreviewSubject = trim($subject);
        $this->emailPreviewBody    = $body;

        if ($recipient !== null && filter_var(trim($recipient), FILTER_VALIDATE_EMAIL)) {
            $this->emailPreviewRecipient = trim($recipient);
        }
    }

    // ── Méthodes protégées / internes ────────────────────────────────

    /** Req 4.3 */
    protected function openEmailPreview(): void
    {
        $payload = $this->getEmailPreviewPayload();
        if (! $payload) {
            return;
        }

        $this->showEmailPreview             = true;
        $this->emailPreviewRecipient        = $payload['recipient'];
        $this->emailPreviewSubject          = $payload['subject'];
        $this->emailPreviewBody             = $payload['body'];
        $this->emailPreviewOriginalSubject  = $payload['subject'];
        $this->emailPreviewOriginalBody     = $payload['body'];
        $this->emailPreviewConfirmed        = false;
    }

    /** Req 4.8 */
    protected function resetEmailPreviewState(): void
    {
        $this->showEmailPreview            = false;
        $this->emailPreviewConfirmed       = false;
        $this->emailPreviewMode            = 'status';
        $this->emailPreviewRecipient       = null;
        $this->emailPreviewSubject         = null;
        $this->emailPreviewBody            = null;
        $this->emailPreviewOriginalSubject = null;
        $this->emailPreviewOriginalBody    = null;
    }

    /**
     * Construit le payload {recipient, subject, body}.
     * Req 4.2 — conservé public pour rétrocompatibilité PhoningWorkflowPreviewTest (Req 13.2)
     */
    public function getEmailPreviewPayload(): ?array
    {
        $mailable = $this->getPreviewMailableForStatut($this->statut_resultat ?? '');
        if (! $mailable) {
            return null;
        }

        $recipient = $this->resolvePreviewRecipient($this->statut_resultat ?? '');

        return [
            'recipient' => $recipient ?? '',
            'subject'   => $this->getMailableSubject($mailable),
            'body'      => $this->getMailableBody($mailable),
        ];
    }

    // ── Factory Mailables ────────────────────────────────────────────

    /** Req 4.10 */
    protected function getPreviewMailableForStatut(string $statut): ?Mailable
    {
        $contact = $this->currentContact ?? null;

        // currentContact peut être un array (HasContactQueue) ou un Model
        $prospect = $contact instanceof Prospect
            ? $contact
            : ($contact !== null && is_array($contact)
                ? Prospect::find($contact['id'] ?? null)
                : null);

        if (! $prospect instanceof Prospect) {
            return null;
        }

        return match ($statut) {
            'rdv'     => $this->buildPreviewRdvMailable($prospect),
            'bloc'    => new PriseContactBlocMail($prospect, [
                'nom'       => $prospect->interlocuteur_nom,
                'fonction'  => $prospect->interlocuteur_fonction,
                'email'     => $prospect->interlocuteur_email,
                'telephone' => $prospect->interlocuteur_telephone,
            ]),
            'ncse_50' => new ContactSansCSEMail($prospect, [
                'nom'        => $prospect->interlocuteur_nom,
                'fonction'   => $prospect->interlocuteur_fonction,
                'email'      => $prospect->interlocuteur_email,
                'telephone'  => $prospect->interlocuteur_telephone,
                'nb_salaries' => $prospect->nb_salaries,
            ]),
            'cse_hz'  => new GenericProspectionMail('interne.cse_hors_zone', [
                'entreprise_nom' => $prospect->nom,
                'elu_nom'        => $prospect->interlocuteur_nom,
                'elu_email'      => $prospect->interlocuteur_email,
                'elu_telephone'  => $prospect->interlocuteur_telephone,
                'departement'    => $prospect->departement,
                'ville'          => $prospect->ville,
            ]),
            default   => null,
        };
    }

    /** Req 4.11 */
    protected function buildPreviewRdvMailable(Prospect $prospect): ?Mailable
    {
        if (empty($this->rappel_date)) {
            return null;
        }

        $dateHeure = $this->rappel_date . ' ' . ($this->rappel_heure ?: '09:00');

        $rdv = new RendezVous([
            'rdvable_type'       => Prospect::class,
            'rdvable_id'         => $prospect->id,
            'date_heure'         => $dateHeure,
            'lieu'               => $this->lieu_rdv ?: null,
            'teleprospecteur_id' => Auth::id(),
            'interlocuteur_nom'  => $this->interlocuteur_nom ?: $prospect->fallback_interlocuteur_nom,
            'interlocuteur_tel'  => $this->interlocuteur_telephone ?: $prospect->fallback_interlocuteur_telephone,
            'interlocuteur_email' => $this->interlocuteur_email ?: $prospect->fallback_interlocuteur_email,
        ]);

        $rdv->setRelation('commercial', $prospect->commercial);

        return new ConfirmationRdvProspectMail($prospect, $rdv);
    }

    /** Req 4.12 */
    protected function resolvePreviewRecipient(string $statut): ?string
    {
        $contact = $this->currentContact ?? null;
        $prospect = $contact instanceof Prospect
            ? $contact
            : ($contact !== null && is_array($contact)
                ? Prospect::find($contact['id'] ?? null)
                : null);

        if (! $prospect instanceof Prospect) {
            return null;
        }

        return match ($statut) {
            'rdv', 'bloc', 'ncse_50' => $prospect->interlocuteur_email
                ?: $prospect->fallback_interlocuteur_email
                ?: $this->localPreviewFallbackEmail(),
            'cse_hz' => app()->environment('production')
                ? config('aopia.mail.cse_hors_zone_email', 'bruno@ns-conseil.com')
                : ($this->localPreviewFallbackEmail() ?: config('aopia.mail.preview_fallback_email', 'bruno@ns-conseil.com')),
            default => null,
        };
    }

    /** Req 4.13 */
    protected function buildProspectionMailContext(?RendezVous $rdv = null): array
    {
        $context = ['rdv' => $rdv];

        if ($this->emailPreviewConfirmed
            && $this->emailPreviewSubject !== null
            && $this->emailPreviewBody !== null
        ) {
            $context['email_preview_subject'] = $this->emailPreviewSubject;
            $context['email_preview_body']    = $this->emailPreviewBody;
        }

        if ($this->emailPreviewConfirmed && filled($this->emailPreviewRecipient)) {
            $context['email_preview_to'] = $this->emailPreviewRecipient;
        }

        return $context;
    }

    protected function localPreviewFallbackEmail(): ?string
    {
        if (app()->environment('production')) {
            return null;
        }

        return config('mail.redirect_all_to');
    }

    // ── Helpers réflexion ────────────────────────────────────────────

    protected function getMailableSubject(Mailable $mailable): string
    {
        if (method_exists($mailable, 'getRenderedSubject')) {
            return $this->invokeProtectedMethod($mailable, 'getRenderedSubject');
        }

        return $mailable->envelope()->subject;
    }

    protected function getMailableBody(Mailable $mailable): string
    {
        if (method_exists($mailable, 'getRenderedBody')) {
            return $this->invokeProtectedMethod($mailable, 'getRenderedBody');
        }

        return $mailable->render();
    }

    protected function invokeProtectedMethod(object $object, string $method, array $parameters = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $parameters);
    }
}
