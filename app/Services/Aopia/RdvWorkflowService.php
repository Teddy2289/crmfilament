<?php

namespace App\Services\Aopia;

use App\Mail\ConfirmationRdvCseMail;
use App\Mail\InvitationAgendaCommercialMail;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Orchestre les workflows RDV du CDC :
 * - WF1 : confirmation email au CSE
 * - WF2 : génération PDF récap (via FicheGenerationService)
 * - WF3 : invitation agenda au commercial + CC
 */
class RdvWorkflowService
{
    public function __construct(
        protected FicheGenerationService $ficheService,
    ) {}

    /**
     * WF1 — Envoie l'email de confirmation RDV au CSE.
     */
    public function envoyerConfirmationCse(RendezVous $rdv): bool
    {
        $prospect = $this->resolveProspect($rdv);
        if (! $prospect || ! $prospect->interlocuteur_email) {
            Log::warning("WF1: pas d'email interlocuteur pour RDV #{$rdv->id}");

            return false;
        }

        $teleprospecteur = $rdv->teleprospecteur ?? User::find($rdv->teleprospecteur_id);
        if (! $teleprospecteur) {
            $teleprospecteur = auth()->user();
        }

        Mail::to($prospect->interlocuteur_email)
            ->send(new ConfirmationRdvCseMail($rdv, $prospect, $teleprospecteur));

        $rdv->confirmer();

        Log::info("WF1: confirmation RDV #{$rdv->id} envoyée à {$prospect->interlocuteur_email}");

        return true;
    }

    /**
     * WF2 — Génère le PDF récap et l'attache au RDV.
     */
    public function genererPdfRecap(RendezVous $rdv): ?string
    {
        $prospect = $this->resolveProspect($rdv);
        if (! $prospect) {
            return null;
        }

        try {
            $path = $this->ficheService->genererFicheBleuePdf($prospect, $rdv);
            if ($path) {
                $rdv->ajouterPDF($path);
                Log::info("WF2: PDF récap généré pour RDV #{$rdv->id} → {$path}");
            }

            return $path;
        } catch (\Throwable $e) {
            Log::error("WF2: échec génération PDF RDV #{$rdv->id} — {$e->getMessage()}");

            return null;
        }
    }

    /**
     * WF3 — Envoie l'invitation agenda au commercial avec PDF + audio.
     */
    public function envoyerInvitationCommercial(RendezVous $rdv): bool
    {
        $prospect = $this->resolveProspect($rdv);
        if (! $prospect) {
            return false;
        }

        $commercial = $rdv->commercial ?? User::find($rdv->commercial_id);
        if (! $commercial?->email) {
            Log::warning("WF3: pas de commercial/email pour RDV #{$rdv->id}");

            return false;
        }

        $teleprospecteur = $rdv->teleprospecteur ?? User::find($rdv->teleprospecteur_id) ?? auth()->user();

        Mail::to($commercial->email)
            ->send(new InvitationAgendaCommercialMail($rdv, $prospect, $teleprospecteur));

        $rdv->envoyerInvitation();

        Log::info("WF3: invitation RDV #{$rdv->id} envoyée à {$commercial->email}");

        return true;
    }

    /**
     * Enchaîne WF1 + WF2 + WF3 quand un RDV est qualifié.
     */
    public function executerWorkflowComplet(RendezVous $rdv): array
    {
        $resultats = [
            'confirmation_cse' => false,
            'pdf_recap' => null,
            'invitation_commercial' => false,
        ];

        // WF1
        if (! $rdv->email_confirmation_envoye) {
            $resultats['confirmation_cse'] = $this->envoyerConfirmationCse($rdv);
        }

        // WF2
        if (! $rdv->pdf_recap) {
            $resultats['pdf_recap'] = $this->genererPdfRecap($rdv);
        }

        // WF3 (après PDF pour pouvoir l'attacher)
        if (! $rdv->email_invitation_envoye && $rdv->commercial_id) {
            $resultats['invitation_commercial'] = $this->envoyerInvitationCommercial($rdv);
        }

        return $resultats;
    }

    protected function resolveProspect(RendezVous $rdv): ?Prospect
    {
        if ($rdv->rdvable_type === Prospect::class) {
            return $rdv->rdvable ?? Prospect::find($rdv->rdvable_id);
        }

        return null;
    }
}
