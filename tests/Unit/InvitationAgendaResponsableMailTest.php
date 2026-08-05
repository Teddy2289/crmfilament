<?php

namespace Tests\Unit;

use App\Mail\InvitationAgendaResponsableMail;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class InvitationAgendaResponsableMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_ignores_remote_pdf_when_url_is_inaccessible(): void
    {
        Http::fake([
            'https://example.com/remote-fiche.pdf' => Http::response('Not found', 404),
        ]);

        Log::shouldReceive('warning')->once();

        $prospect = Prospect::factory()->make([
            'raison_sociale' => 'Entreprise Test',
        ]);

        $commercial = User::factory()->make([
            'email' => 'commercial@test.local',
            'prenom' => 'Jean',
        ]);

        $rdv = new RendezVous([
            'id' => 1,
            'rdvable_type' => Prospect::class,
            'rdvable_id' => 1,
            'commercial_id' => 1,
            'date_heure' => now()->addDays(1),
            'lieu' => 'Salle de réunion',
        ]);

        $rdv->setRelation('rdvable', $prospect);
        $rdv->setRelation('commercial', $commercial);

        $mail = new InvitationAgendaResponsableMail(
            $prospect,
            $rdv,
            'https://example.com/remote-fiche.pdf',
            null,
        );

        $attachments = $mail->attachments();

        $this->assertCount(1, $attachments);
    }
}
