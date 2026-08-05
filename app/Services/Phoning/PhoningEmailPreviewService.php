<?php

namespace App\Services\Phoning;

use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\ContactSansCSEMail;
use App\Mail\GenericProspectionMail;
use App\Mail\PriseContactBlocMail;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;

/**
 * Construit les payloads d'aperçu email avant envoi phoning.
 * Requirements 9.1–9.6
 */
class PhoningEmailPreviewService
{
    /** Statuts qui déclenchent un aperçu email. */
    private const EMAIL_STATUTS = ['rdv', 'bloc', 'ncse_50', 'cse_hz'];

    /**
     * Construit le payload {recipient, subject, body} pour l'aperçu.
     * Retourne null si le statut ne déclenche pas d'email.
     * Req 9.2, 9.3, 9.4
     *
     * @param  array<string, mixed>  $formFields
     * @return array{recipient: string, subject: string, body: string}|null
     */
    public function buildPayload(string $statut, Prospect $contact, array $formFields): ?array
    {
        $mailable = $this->makeMailable($statut, $contact, $formFields);

        if ($mailable === null) {
            return null;
        }

        $recipient = $this->resolveRecipient($statut, $contact);
        $subject   = $this->getMailableSubject($mailable);
        $body      = $this->getMailableBody($mailable);

        return [
            'recipient' => $recipient ?? '',
            'subject'   => $subject,
            'body'      => $body,
        ];
    }

    /**
     * Crée le Mailable correspondant au statut.
     * Req 9.5
     */
    public function makeMailable(string $statut, Prospect $contact, array $fields): ?Mailable
    {
        return match ($statut) {
            'rdv' => $this->buildRdvMailable($contact, $fields),
            'bloc' => new PriseContactBlocMail($contact, [
                'nom'       => $fields['interlocuteur_nom']      ?? $contact->interlocuteur_nom,
                'fonction'  => $fields['interlocuteur_fonction'] ?? $contact->interlocuteur_fonction,
                'email'     => $fields['interlocuteur_email']    ?? $contact->interlocuteur_email,
                'telephone' => $fields['interlocuteur_telephone'] ?? $contact->interlocuteur_telephone,
            ]),
            'ncse_50' => new ContactSansCSEMail($contact, [
                'nom'        => $fields['interlocuteur_nom']      ?? $contact->interlocuteur_nom,
                'fonction'   => $fields['interlocuteur_fonction'] ?? $contact->interlocuteur_fonction,
                'email'      => $fields['interlocuteur_email']    ?? $contact->interlocuteur_email,
                'telephone'  => $fields['interlocuteur_telephone'] ?? $contact->interlocuteur_telephone,
                'nb_salaries' => $contact->nb_salaries,
            ]),
            'cse_hz' => new GenericProspectionMail('interne.cse_hors_zone', [
                'entreprise_nom'  => $contact->nom,
                'elu_nom'         => $fields['interlocuteur_nom'] ?? $contact->interlocuteur_nom,
                'elu_email'       => $fields['interlocuteur_email'] ?? $contact->interlocuteur_email,
                'elu_telephone'   => $fields['interlocuteur_telephone'] ?? $contact->interlocuteur_telephone,
                'departement'     => $contact->departement,
                'ville'           => $contact->ville,
            ]),
            default => null,
        };
    }

    /**
     * Résout le destinataire principal pour le statut donné.
     * Req 9.6
     */
    public function resolveRecipient(string $statut, Prospect $contact): ?string
    {
        return match ($statut) {
            'rdv', 'bloc', 'ncse_50' => $contact->interlocuteur_email
                ?: $contact->fallback_interlocuteur_email
                ?: $this->fallbackEmail(),
            'cse_hz' => app()->environment('production')
                ? 'bruno@ns-conseil.com'
                : ($this->fallbackEmail() ?: 'bruno@ns-conseil.com'),
            default => null,
        };
    }

    // ── Helpers privés ───────────────────────────────────────────────

    private function buildRdvMailable(Prospect $contact, array $fields): ?Mailable
    {
        if (empty($fields['rappel_date'])) {
            return null;
        }

        $dateHeure = $fields['rappel_date'] . ' ' . ($fields['rappel_heure'] ?: '09:00');

        $rdv = new RendezVous([
            'rdvable_type'       => Prospect::class,
            'rdvable_id'         => $contact->id,
            'date_heure'         => $dateHeure,
            'lieu'               => $fields['lieu_rdv'] ?? null,
            'teleprospecteur_id' => Auth::id(),
            'interlocuteur_nom'  => $fields['interlocuteur_nom'] ?? $contact->fallback_interlocuteur_nom,
            'interlocuteur_tel'  => $fields['interlocuteur_telephone'] ?? $contact->fallback_interlocuteur_telephone,
            'interlocuteur_email' => $fields['interlocuteur_email'] ?? $contact->fallback_interlocuteur_email,
        ]);

        $rdv->setRelation('commercial', $contact->commercial);

        return new ConfirmationRdvProspectMail($contact, $rdv);
    }

    private function getMailableSubject(Mailable $mailable): string
    {
        if (method_exists($mailable, 'getRenderedSubject')) {
            $ref = new \ReflectionMethod($mailable, 'getRenderedSubject');
            $ref->setAccessible(true);
            return (string) $ref->invoke($mailable);
        }

        return $mailable->envelope()->subject;
    }

    private function getMailableBody(Mailable $mailable): string
    {
        if (method_exists($mailable, 'getRenderedBody')) {
            $ref = new \ReflectionMethod($mailable, 'getRenderedBody');
            $ref->setAccessible(true);
            return (string) $ref->invoke($mailable);
        }

        return $mailable->render();
    }

    private function fallbackEmail(): ?string
    {
        if (app()->environment('production')) {
            return null;
        }

        return config('mail.redirect_all_to');
    }
}
