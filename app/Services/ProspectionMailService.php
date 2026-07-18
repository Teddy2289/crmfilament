<?php
namespace App\Services;

use App\Mail\ContactSansCSEMail;
use App\Mail\PriseContactBlocMail;
use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\GenericProspectionMail;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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

        $emailInterlocuteur = $prospect->interlocuteur_email ?: $this->fallbackEmail();

        Log::info("MAIL DEBUG: envoyerRdv", [
            'email_interlocuteur_utilise' => $emailInterlocuteur,
            'via_fallback_interlocuteur' => ! $prospect->interlocuteur_email,
        ]);

        if ($emailInterlocuteur) {
            Mail::to($emailInterlocuteur)->queue(new ConfirmationRdvProspectMail($prospect, $rdv));
            $rdv->confirmer();
        }

        // L'invitation au commercial (avec fiche + enregistrement audio de l'appel)
        // est envoyée par SendInvitationAgendaJob une fois les pièces jointes prêtes
        // (l'enregistrement Ringover arrive de façon asynchrone après le raccroché).
    }

    protected function envoyerBloc(Prospect $prospect, array $contexte): void
    {
        $email = $prospect->interlocuteur_email ?: $this->fallbackEmail();

        Log::info("MAIL DEBUG: envoyerBloc", [
            'email_utilise' => $email,
            'via_fallback' => ! $prospect->interlocuteur_email,
        ]);

        if (! $email) {
            Log::warning("MAIL DEBUG: envoyerBloc — aucun email disponible, mail annulé pour prospect #{$prospect->id}");
            return;
        }

        Mail::to($email)->queue(new PriseContactBlocMail($prospect, [
            'nom' => $prospect->interlocuteur_nom,
            'fonction' => $prospect->interlocuteur_fonction,
            'email' => $prospect->interlocuteur_email,
            'telephone' => $prospect->interlocuteur_telephone,
        ]));
    }

    protected function envoyerNcse50(Prospect $prospect, array $contexte): void
    {
        $email = $prospect->interlocuteur_email ?: $this->fallbackEmail();

        Log::info("MAIL DEBUG: envoyerNcse50", [
            'email_utilise' => $email,
            'via_fallback' => ! $prospect->interlocuteur_email,
        ]);

        if (! $email) {
            Log::warning("MAIL DEBUG: envoyerNcse50 — aucun email disponible, mail annulé pour prospect #{$prospect->id}");
            return;
        }

        Mail::to($email)->queue(new ContactSansCSEMail($prospect, [
            'nom' => $prospect->interlocuteur_nom,
            'fonction' => $prospect->interlocuteur_fonction,
            'email' => $prospect->interlocuteur_email,
            'telephone' => $prospect->interlocuteur_telephone,
            'nb_salaries' => $prospect->nb_salaries,
        ]));
    }

    protected function envoyerHorsZone(Prospect $prospect, array $contexte): void
    {
        // Coordonnées transmises à Bruno pour traitement (CSE centralisé hors zone).
        // En local/staging, redirigé vers l'adresse de fallback si 'bruno@ns-conseil.com'
        // n'est pas voulu comme destinataire de test.
        $destinataire = app()->environment('production')
            ? 'bruno@ns-conseil.com'
            : ($this->fallbackEmail() ?: 'bruno@ns-conseil.com');

        Log::info("MAIL DEBUG: envoyerHorsZone", ['destinataire' => $destinataire]);

        Mail::to($destinataire)->queue(new GenericProspectionMail(
            templateKey: 'interne.cse_hors_zone',
            variables: [
                'entreprise_nom' => $prospect->nom,
                'elu_nom' => $prospect->interlocuteur_nom,
                'elu_email' => $prospect->interlocuteur_email,
                'elu_telephone' => $prospect->interlocuteur_telephone,
                'departement' => $prospect->departement,
                'ville' => $prospect->ville,
            ],
        ));
    }

    /**
     * En local/staging, permet de forcer l'envoi même sans destinataire réel,
     * pour valider le contenu des templates. Ne jamais activer en prod.
     */
    protected function fallbackEmail(): ?string
    {
        if (app()->environment('production')) {
            return null;
        }

        return config('mail.redirect_all_to');
    }
}