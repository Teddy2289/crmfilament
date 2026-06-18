<?php

namespace App\Observers;

use App\Enums\RendezVousStatut;
use App\Models\RendezVous;
use App\Services\GoogleCalendarService;
use App\Models\Prospect;
use App\Services\Aopia\RdvWorkflowService;

class RendezVousObserver
{
    public function __construct(
        private GoogleCalendarService $google,
        private RdvWorkflowService $rdvWorkflow,
    ) {}

    public function created(RendezVous $rdv): void
    {
        if ($rdv->statut === RendezVousStatut::Annule) {
            return;
        }

        $this->google->createEvent($rdv);
        // WF1: envoi confirmation au CSE si RDV lié à un prospect
        if ($rdv->statut === RendezVousStatut::Planifie
            && $rdv->rdvable_type === Prospect::class
            && ! $rdv->email_confirmation_envoye
        ) {
            rescue(fn () => $this->rdvWorkflow->envoyerConfirmationCse($rdv));
        }
        $this->clearCache($rdv);
    }

    public function updated(RendezVous $rdv): void
    {
        if ($rdv->statut === RendezVousStatut::Annule && $rdv->google_event_id) {
            $this->google->deleteEvent($rdv);
        } elseif ($rdv->google_event_id) {
            $this->google->updateEvent($rdv);
        } else {
            $this->google->createEvent($rdv);
        }

        $this->clearCache($rdv);
    }

    public function deleted(RendezVous $rdv): void
    {
        if ($rdv->google_event_id) {
            $this->google->deleteEvent($rdv);
        }
        $this->clearCache($rdv);
    }

    public function restored(RendezVous $rdv): void
    {
        $this->google->createEvent($rdv);
        $this->clearCache($rdv);
    }

    private function clearCache(RendezVous $rdv): void
    {
        $user = $rdv->commercial ?? $rdv->teleprospecteur;
        if ($user) {
            $this->google->clearEventsCache($user);
        }
    }
}
