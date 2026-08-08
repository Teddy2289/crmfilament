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
        $sourceData = is_array($this->currentContactData) ? $this->currentContactData : [];

        if ($this->currentContact instanceof Prospect) {
            $sourceData = array_merge($sourceData, $this->currentContact->toArray());
        } elseif (is_array($this->currentContact)) {
            $sourceData = array_merge($sourceData, $this->currentContact);
        }

        $nom = $this->resolveEmailTemplateValue($sourceData, ['nom', 'raison_sociale', 'entreprise_nom']);
        $prenom = $this->resolveEmailTemplateValue($sourceData, ['prenom', 'first_name']);
        $interlocuteurPrenom = $this->resolveEmailTemplateValue($sourceData, ['interlocuteur_prenom', 'prenom']);
        $interlocuteurNom = $this->resolveEmailTemplateValue($sourceData, ['interlocuteur_nom', 'nom']);
        $contactPrenomNom = trim(implode(' ', array_filter([$interlocuteurPrenom, $interlocuteurNom])));

        $variables = array_filter([
            'nom'                    => $nom,
            'prenom'                 => $prenom,
            'raison_sociale'        => $nom,
            'entreprise_nom'        => $nom,
            'telephone'             => $this->resolveEmailTemplateValue($sourceData, ['telephone', 'telephone_direct', 'telephone_mobile']),
            'telephone_alt'         => $this->resolveEmailTemplateValue($sourceData, ['telephone_alt', 'telephone_perso']),
            'email'                 => $this->resolveEmailTemplateValue($sourceData, ['email', 'email_perso']),
            'ville'                 => $this->resolveEmailTemplateValue($sourceData, ['ville', 'commune']),
            'code_postal'           => $this->resolveEmailTemplateValue($sourceData, ['code_postal', 'cp']),
            'departement'           => $this->resolveEmailTemplateValue($sourceData, ['departement', 'department']),
            'adresse'               => $this->resolveEmailTemplateValue($sourceData, ['adresse', 'adresse_complete']),
            'adresse_complete'      => $this->resolveEmailTemplateValue($sourceData, ['adresse_complete', 'adresse']),
            'interlocuteur_nom'     => $interlocuteurNom,
            'interlocuteur_prenom'  => $interlocuteurPrenom,
            'interlocuteur_email'   => $this->resolveEmailTemplateValue($sourceData, ['interlocuteur_email', 'email_interlocuteur']),
            'interlocuteur_telephone' => $this->resolveEmailTemplateValue($sourceData, ['interlocuteur_telephone', 'telephone_interlocuteur']),
            'interlocuteur_fonction' => $this->resolveEmailTemplateValue($sourceData, ['interlocuteur_fonction', 'fonction']),
            'email_general_standard' => $this->resolveEmailTemplateValue($sourceData, ['email_general_standard', 'email_standard']),
            'secteur_activite'      => $this->resolveEmailTemplateValue($sourceData, ['secteur_activite', 'activite']),
            'date'                  => now()->translatedFormat('d/m/Y'),
            'heure'                 => now()->format('H:i'),
            'lieu'                  => $this->lieu_rdv ?: $this->resolveEmailTemplateValue($sourceData, ['lieu_rdv', 'lieu']),
            'rdv_date'              => $this->rappel_date ?: $this->resolveEmailTemplateValue($sourceData, ['rappel_date', 'date_rdv']),
            'rdv_heure'             => $this->rappel_heure ?: $this->resolveEmailTemplateValue($sourceData, ['rappel_heure', 'heure_rdv']),
            'rdv_lieu'              => $this->lieu_rdv ?: $this->resolveEmailTemplateValue($sourceData, ['lieu_rdv', 'lieu']),
            'contact_prenom_nom'    => $contactPrenomNom ?: $nom,
            'cse_prenom'            => $interlocuteurPrenom,
            'cse_nom'               => $interlocuteurNom,
            'cse_prenom_nom'        => $contactPrenomNom ?: $nom,
            'teleprospecteur_nom'   => trim((string) (Auth::user()?->nom ?? '')),
            'teleprospecteur_prenom' => trim((string) (Auth::user()?->prenom ?? '')),
            'teleprospecteur_prenom_nom' => trim((string) ((Auth::user()?->prenom ?? '') . ' ' . (Auth::user()?->nom ?? ''))),
            'commercial_nom'        => $this->resolveEmailTemplateValue($sourceData, ['commercial', 'commercial_nom']),
            'commercial_prenom_nom' => $this->resolveEmailTemplateValue($sourceData, ['commercial', 'commercial_nom']),
        ], fn ($value) => $value !== null && $value !== '');

        return $variables;
    }

    protected function resolveEmailTemplateValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }

        return null;
    }

    public function getDetectedTemplateVariables(): array
    {
        $content = trim((string) ($this->emailTabSubject ?? '') . "\n" . (string) ($this->emailTabBody ?? ''));

        if (blank($content)) {
            return [];
        }

        preg_match_all('/\{\{\s*([a-z0-9_]+)\s*\}\}/i', $content, $matches);

        $variables = [];
        $resolved = $this->buildEmailTemplateVariables();

        foreach (array_unique($matches[1]) as $variable) {
            $normalized = strtolower($variable);
            $variables[$normalized] = $resolved[$normalized] ?? null;
        }

        return array_filter($variables, fn ($value) => $value !== null && $value !== '');
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
