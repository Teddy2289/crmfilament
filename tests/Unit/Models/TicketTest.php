<?php

namespace Tests\Unit\Models;

use App\Enums\NiveauPriorite;
use App\Enums\TicketStatut;
use App\Models\Ticket;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TicketTest extends TestCase
{
    #[Test]
    public function statut_label_attribute(): void
    {
        $ticket = new Ticket;
        $ticket->statut = TicketStatut::AppelRecu;

        $this->assertEquals('Appel reçu', $ticket->statut_label);
    }

    #[Test]
    public function statut_label_with_null_statut(): void
    {
        $ticket = new Ticket;
        $ticket->statut = null;

        $this->assertEquals('Non défini', $ticket->statut_label);
    }

    #[Test]
    public function statut_color_attribute(): void
    {
        $ticket = new Ticket;
        $ticket->statut = TicketStatut::AppelRecu;

        $this->assertEquals('info', $ticket->statut_color);
    }

    #[Test]
    public function statut_color_with_null(): void
    {
        $ticket = new Ticket;
        $ticket->statut = null;

        $this->assertEquals('gray', $ticket->statut_color);
    }

    #[Test]
    public function statut_icon_with_null(): void
    {
        $ticket = new Ticket;
        $ticket->statut = null;

        $this->assertEquals('heroicon-o-question-mark-circle', $ticket->statut_icon);
    }

    #[Test]
    public function priorite_label_attribute(): void
    {
        $ticket = new Ticket;
        $ticket->niveau_priorite = NiveauPriorite::Urgence;

        $this->assertEquals('Urgence', $ticket->priorite_label);
    }

    #[Test]
    public function priorite_label_with_null(): void
    {
        $ticket = new Ticket;
        $ticket->niveau_priorite = null;

        $this->assertEquals('Non défini', $ticket->priorite_label);
    }

    #[Test]
    public function duree_traitement_zero_without_date_creation(): void
    {
        $ticket = new Ticket;
        $ticket->date_creation = null;

        $this->assertEquals(0, $ticket->duree_traitement_minutes);
    }

    #[Test]
    public function statut_ordre_attribute(): void
    {
        $ticket = new Ticket;
        $ticket->statut = TicketStatut::AppelRecu;

        $this->assertEquals(1, $ticket->statut_ordre);
    }

    #[Test]
    public function statut_ordre_with_null(): void
    {
        $ticket = new Ticket;
        $ticket->statut = null;

        $this->assertEquals(0, $ticket->statut_ordre);
    }

    #[Test]
    public function progression_pourcentage(): void
    {
        $ticket = new Ticket;
        $ticket->statut = TicketStatut::DossierCloture;

        $this->assertEquals(100, $ticket->progression_pourcentage);
    }

    #[Test]
    public function progression_pourcentage_with_null(): void
    {
        $ticket = new Ticket;
        $ticket->statut = null;

        $this->assertEquals(0, $ticket->progression_pourcentage);
    }
}
