<?php

namespace App\Observers;

use App\Models\RendezVous;
use App\Services\GoogleCalendarService;
use App\Enums\RendezVousStatut;

class RendezVousObserver
{
    public function __construct(private GoogleCalendarService $google) {}

    public function created(RendezVous $rdv): void
    {
        if ($rdv->statut === RendezVousStatut::Annule) return;

        $this->google->createEvent($rdv);
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
