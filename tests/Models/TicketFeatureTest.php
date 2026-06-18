<?php

namespace Tests\Feature\Models;

use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use App\Enums\TicketStatut;
use App\Models\ContactParticulier;
use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createTicket(array $overrides = []): Ticket
    {
        $contact = ContactParticulier::create([
            'nom' => 'Client',
            'prenom' => 'Test',
            'telephone' => '0612345678',
        ]);

        return Ticket::create(array_merge([
            'reference' => 'TK-'.uniqid(),
            'statut' => TicketStatut::AppelRecu,
            'niveau_priorite' => NiveauPriorite::Standard,
            'corps_de_metier' => CorpsDeMetier::Plomberie,
            'date_creation' => now(),
            'contact_particulier_id' => $contact->id,
        ], $overrides));
    }

    #[Test]
    public function duree_traitement_minutes_returns_integer(): void
    {
        $now = Carbon::now();
        $ticket = new Ticket;
        $ticket->date_creation = $now->copy();
        $ticket->date_cloture = $now->copy()->addMinutes(45);

        $this->assertIsInt($ticket->duree_traitement_minutes);
    }

    #[Test]
    public function duree_traitement_formatee_returns_string(): void
    {
        $now = Carbon::now();
        $ticket = new Ticket;
        $ticket->date_creation = $now->copy();
        $ticket->date_cloture = $now->copy()->addMinutes(45);

        $this->assertIsString($ticket->duree_traitement_formatee);
        $this->assertStringContainsString('min', $ticket->duree_traitement_formatee);
    }

    #[Test]
    public function duree_traitement_formatee_without_dates(): void
    {
        $ticket = new Ticket;
        $ticket->date_creation = null;

        $this->assertEquals('0 min', $ticket->duree_traitement_formatee);
    }

    #[Test]
    public function scope_actifs(): void
    {
        $this->createTicket(['statut' => TicketStatut::AppelRecu]);
        $this->createTicket(['statut' => TicketStatut::DossierCloture]);
        $this->createTicket(['statut' => TicketStatut::ClotureSatisfait]);

        $this->assertCount(1, Ticket::actifs()->get());
    }

    #[Test]
    public function scope_clotures(): void
    {
        $this->createTicket(['statut' => TicketStatut::AppelRecu]);
        $this->createTicket(['statut' => TicketStatut::DossierCloture]);
        $this->createTicket(['statut' => TicketStatut::ClotureSatisfait]);

        $this->assertCount(2, Ticket::clotures()->get());
    }

    #[Test]
    public function scope_bloquants(): void
    {
        $this->createTicket(['statut' => TicketStatut::FicheIncomplete]);
        $this->createTicket(['statut' => TicketStatut::ReclamationOuverte]);
        $this->createTicket(['statut' => TicketStatut::AppelRecu]);

        $this->assertCount(2, Ticket::bloquants()->get());
    }

    #[Test]
    public function scope_by_statut(): void
    {
        $this->createTicket(['statut' => TicketStatut::AppelRecu]);
        $this->createTicket(['statut' => TicketStatut::EnQualification]);

        $this->assertCount(1, Ticket::byStatut(TicketStatut::AppelRecu)->get());
    }

    #[Test]
    public function scope_by_priorite(): void
    {
        $this->createTicket(['niveau_priorite' => NiveauPriorite::Urgence]);
        $this->createTicket(['niveau_priorite' => NiveauPriorite::Standard]);

        $this->assertCount(1, Ticket::byPriorite(NiveauPriorite::Urgence)->get());
    }

    #[Test]
    public function scope_urgents(): void
    {
        $this->createTicket(['niveau_priorite' => NiveauPriorite::Urgence, 'statut' => TicketStatut::AppelRecu]);
        $this->createTicket(['niveau_priorite' => NiveauPriorite::Urgence, 'statut' => TicketStatut::DossierCloture]);
        $this->createTicket(['niveau_priorite' => NiveauPriorite::Standard, 'statut' => TicketStatut::AppelRecu]);

        $this->assertCount(1, Ticket::urgents()->get());
    }

    #[Test]
    public function soft_deletes(): void
    {
        $ticket = $this->createTicket();
        $ticket->delete();

        $this->assertSoftDeleted($ticket);
    }
}
