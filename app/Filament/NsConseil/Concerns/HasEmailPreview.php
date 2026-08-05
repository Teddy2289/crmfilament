<?php

namespace App\Filament\NsConseil\Concerns;

use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\ContactSansCSEMail;
use App\Mail\GenericProspectionMail;
use App\Mail\PriseContactBlocMail;
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

        $this->submitResult();
    }

    /** Req 4.5 */
    public function cancelEmailPreview(): void
    {
        $this->showEmailPreview      = false;
        $this->emailPreviewConfirmed = false;
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
                ? 'bruno@ns-conseil.com'
                : ($this->localPreviewFallbackEmail() ?: 'bruno@ns-conseil.com'),
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
