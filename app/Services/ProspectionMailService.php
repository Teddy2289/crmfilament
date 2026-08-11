<?php
namespace App\Services;

use App\Jobs\SendProspectionMailJob;
use App\Mail\PriseContactBlocMail;
use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\GenericProspectionMail;
use App\Mail\PreviewableProspectionMail;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\EmailConfiguration;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProspectionMailService
{
    /**
     * Point d'entrée unique : envoie le(s) mail(s) correspondant au code
     * de statut phoning, si applicable. Ne fait rien si aucun mail n'est
     * défini pour ce code (ex: nrp, fax, std_nr...).
     */
    public function envoyerPourStatut(string $code, Prospect $prospect, array $contexte = []): void
    {
        Log::info("MAIL DEBUG: entrée envoyerPourStatut", [
            'code' => $code,
            'prospect_id' => $prospect->id,
            'rdv_present' => isset($contexte['rdv']) && $contexte['rdv'] !== null,
            'rdv_id' => $contexte['rdv']?->id,
        ]);

        try {
            match ($code) {
                'rdv' => $this->envoyerRdv($prospect, $contexte),
                'bloc' => $this->envoyerBloc($prospect, $contexte),
                'ncse_50' => $this->envoyerNcse50($prospect, $contexte),
                'cse_hz' => $this->envoyerHorsZone($prospect, $contexte),
                // cse_ni : géré par SendFicheJauneJ7Job (J+7), pas de mail immédiat
                // bloc2, ncse_plus50, cse_zone, rapl_elu, rapl_std : pas de mail externe
                // (fiches internes au commercial, déjà gérées via FicheGenerationService)
                default => null,
            };
        } catch (\Throwable $e) {
            // Un échec d'email ne doit jamais casser l'enregistrement de l'appel
            Log::error("ProspectionMailService: échec envoi mail pour statut [{$code}] / prospect #{$prospect->id} : " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function envoyerRdv(Prospect $prospect, array $contexte): void
    {
        /** @var RendezVous|null $rdv */
        $rdv = $contexte['rdv'] ?? null;

        if (! $rdv) {
            Log::warning("MAIL DEBUG: pas de RDV créé, mail annulé");
            return;
        }

        $emailInterlocuteur = $prospect->interlocuteur_email ?: $prospect->fallback_interlocuteur_email ?: $this->fallbackEmail();

        Log::info("MAIL DEBUG: envoyerRdv", [
            'email_interlocuteur_utilise' => $emailInterlocuteur,
            'via_fallback_interlocuteur' => ! $prospect->interlocuteur_email && (bool) $prospect->fallback_interlocuteur_email,
        ]);

        if ($emailInterlocuteur) {
            $mailable = $this->wrapPreviewableMailable(new ConfirmationRdvProspectMail($prospect, $rdv), $contexte);

            dispatch(new SendProspectionMailJob(
                mailable: $mailable,
                to: $this->resolveDestinataire($contexte, $emailInterlocuteur),
                emailLabel: 'Confirmation RDV',
                prospectId: $prospect->id,
                notifyUserId: Auth::id(),
                sourceEmail: $this->resolveSourceEmail(),
                emailConfigurationId: $this->resolveEmailConfigurationId(),
            ));
            $rdv->confirmer();
        }

        // L'invitation au commercial (avec fiche + enregistrement audio de l'appel)
        // est envoyée par SendInvitationAgendaJob une fois les pièces jointes prêtes
        // (l'enregistrement Ringover arrive de façon asynchrone après le raccroché).
    }

    protected function envoyerBloc(Prospect $prospect, array $contexte): void
    {
        $email = $prospect->interlocuteur_email ?: $prospect->fallback_interlocuteur_email ?: $this->fallbackEmail();

        Log::info("MAIL DEBUG: envoyerBloc", [
            'email_utilise' => $email,
            'via_fallback' => ! $prospect->interlocuteur_email && (bool) $prospect->fallback_interlocuteur_email,
        ]);

        if (! $email) {
            Log::warning("MAIL DEBUG: envoyerBloc — aucun email disponible, mail annulé pour prospect #{$prospect->id}");
            return;
        }

        $mailable = $this->wrapPreviewableMailable(new PriseContactBlocMail($prospect, [
            'nom' => $prospect->interlocuteur_nom,
            'fonction' => $prospect->interlocuteur_fonction,
            'email' => $prospect->interlocuteur_email,
            'telephone' => $prospect->interlocuteur_telephone,
        ]), $contexte);

        dispatch(new SendProspectionMailJob(
            mailable: $mailable,
            to: $this->resolveDestinataire($contexte, $email),
            emailLabel: 'Prise de contact (bloc)',
            prospectId: $prospect->id,
            notifyUserId: Auth::id(),
            sourceEmail: $this->resolveSourceEmail(),
            emailConfigurationId: $this->resolveEmailConfigurationId(),
        ));
    }

    protected function envoyerNcse50(Prospect $prospect, array $contexte): void
    {
        $commercial = $prospect->commercial;
        $email = $commercial?->email;

        Log::info("MAIL DEBUG: envoyerNcse50", [
            'commercial_id' => $commercial?->id,
            'email_utilise' => $email,
        ]);

        if (! $email) {
            Log::warning("MAIL DEBUG: envoyerNcse50 — aucun email disponible, mail annulé pour prospect #{$prospect->id}");
            return;
        }

        $mailable = $this->wrapPreviewableMailable(new GenericProspectionMail(
            'interne.ncse_50_commercial',
            $this->ncse50TemplateVariables($prospect, $commercial)
        ), $contexte);

        dispatch(new SendProspectionMailJob(
            mailable: $mailable,
            to: $email,
            emailLabel: 'Notification commercial - NCSE-50',
            prospectId: $prospect->id,
            notifyUserId: Auth::id(),
            sourceEmail: $this->resolveSourceEmail(),
            emailConfigurationId: $this->resolveEmailConfigurationId(),
            cc: $this->resolveCcUsers($contexte),
        ));
    }

    /** @return array<string, string> */
    protected function ncse50TemplateVariables(Prospect $prospect, ?\App\Models\User $commercial): array
    {
        return [
            'entreprise_nom' => $prospect->nom,
            'nb_salaries' => (string) ($prospect->nb_salaries ?? ''),
            'contact_prenom' => $prospect->interlocuteur_prenom ?? '',
            'contact_nom' => $prospect->interlocuteur_nom ?? '',
            'contact_fonction' => $prospect->interlocuteur_fonction ?? '',
            'contact_email' => $prospect->interlocuteur_email ?? '',
            'contact_telephone' => $prospect->interlocuteur_telephone ?? '',
            'commercial_prenom_nom' => $commercial?->nom_complet ?? '',
            'teleprospecteur_nom' => Auth::user()?->nom_complet ?? '',
        ];
    }

    /** @return array<int, string> */
    protected function resolveCcUsers(array $contexte): array
    {
        $ids = collect($contexte['email_preview_cc_user_ids'] ?? [])
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return \App\Models\User::query()
            ->actifs()
            ->whereIn('id', $ids)
            ->whereNotNull('email')
            ->pluck('email')
            ->all();
    }

    protected function envoyerHorsZone(Prospect $prospect, array $contexte): void
    {
        // Coordonnées transmises à Bruno pour traitement (CSE centralisé hors zone).
        // En local/staging, redirigé vers l'adresse de fallback si 'bruno@ns-conseil.com'
        // n'est pas voulu comme destinataire de test.
        $destinataire = app()->environment('production')
            ? config('aopia.mail.cse_hors_zone_email', 'bruno@ns-conseil.com')
            : ($this->fallbackEmail() ?: config('aopia.mail.preview_fallback_email', 'bruno@ns-conseil.com'));

        Log::info("MAIL DEBUG: envoyerHorsZone", ['destinataire' => $destinataire]);

        $mailable = $this->wrapPreviewableMailable(new GenericProspectionMail(
            templateKey: 'interne.cse_hors_zone',
            variables: [
                'entreprise_nom' => $prospect->nom,
                'elu_nom' => $prospect->interlocuteur_nom,
                'elu_email' => $prospect->interlocuteur_email,
                'elu_telephone' => $prospect->interlocuteur_telephone,
                'departement' => $prospect->departement,
                'ville' => $prospect->ville,
            ],
        ), $contexte);

        dispatch(new SendProspectionMailJob(
            mailable: $mailable,
            to: $this->resolveDestinataire($contexte, $destinataire),
            emailLabel: 'CSE hors zone',
            prospectId: $prospect->id,
            notifyUserId: Auth::id(),
            sourceEmail: $this->resolveSourceEmail(),
            emailConfigurationId: $this->resolveEmailConfigurationId(),
        ));
    }

    /**
     * En local/staging, permet de forcer l'envoi même sans destinataire réel,
     * pour valider le contenu des templates. Ne jamais activer en prod.
     */
    protected function fallbackEmail(): ?string
    {
        // Try the authenticated user's active mail configuration first.
        $userId = Auth::id();

        if ($userId) {
            $config = EmailConfiguration::forUser($userId)
                ->active()
                ->first();

            if ($config && filter_var($config->email, FILTER_VALIDATE_EMAIL)) {
                return $config->email;
            }
        }

        // Prefer the authenticated user's email if available and valid.
        $userEmail = optional(Auth::user())->email;
        if (is_string($userEmail) && filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            return $userEmail;
        }

        // In non-production environments, fall back to the configured redirect address.
        if (! app()->environment('production')) {
            return config('mail.redirect_all_to');
        }

        // In production, if no user email is available, do not send.
        return null;
    }

    protected function resolveDestinataire(array $contexte, string $default): string
    {
        $override = $contexte['email_preview_to'] ?? null;

        if (is_string($override) && filter_var($override, FILTER_VALIDATE_EMAIL)) {
            return $override;
        }

        return $default;
    }

    protected function resolveSourceEmail(): ?string
    {
        return $this->resolveActiveEmailConfiguration()?->email ?: $this->fallbackEmail();
    }

    protected function resolveEmailConfigurationId(): ?int
    {
        return $this->resolveActiveEmailConfiguration()?->id;
    }

    protected function resolveActiveEmailConfiguration(): ?EmailConfiguration
    {
        $userId = Auth::id();

        if (! $userId) {
            return null;
        }

        return EmailConfiguration::forUser($userId)
            ->active()
            ->first();
    }

    protected function wrapPreviewableMailable(Mailable $mailable, array $contexte): Mailable
    {
        if (! array_key_exists('email_preview_subject', $contexte)
            || ! array_key_exists('email_preview_body', $contexte)) {
            return $mailable;
        }

        $subject = $contexte['email_preview_subject'];
        $body = $contexte['email_preview_body'];

        if ($subject === null || $body === null) {
            return $mailable;
        }

        return new PreviewableProspectionMail($subject, $body);
    }
}
