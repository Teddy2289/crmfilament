<?php

namespace Tests\Feature;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\ProspectStatut;
use App\Models\Appel;
use App\Models\CrmProfile;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\Aopia\AopiaProspectWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RingoverQfWorkflowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function qf_requires_complete_ringover_tags_when_rdv_call_exists(): void
    {
        [$teamLeader, $prospect] = $this->readyProspectForQf();
        $this->createValidRdv($prospect);

        $appel = Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $teamLeader->id,
            'type' => EventType::Appel,
            'resultat' => EventResult::Realise,
            'date_heure' => now(),
            'ringover_call_id' => 'rdv-call-1',
            'ringover_department_tag' => 'DEP_45',
            'ringover_status_tag' => null,
            'ringover_tag_is_complete' => false,
            'phoning_status' => 'rdv',
            'enregistrement_audio' => 'https://recording.example.test/rdv-call-1.mp3',
        ]);

        $service = app(AopiaProspectWorkflowService::class);

        $this->assertContains(
            'Tags Ringover DEP_XX + statut',
            $service->champsManquantsPourQf($prospect)
        );

        $appel->update([
            'ringover_status_tag' => 'RDV',
            'ringover_tag_is_complete' => true,
        ]);

        $this->assertNotContains(
            'Tags Ringover DEP_XX + statut',
            $service->champsManquantsPourQf($prospect->refresh())
        );
        $this->assertNotContains(
            'Enregistrement audio joint',
            $service->champsManquantsPourQf($prospect)
        );

        $service->validerQf($prospect, $teamLeader);

        $this->assertSame(ProspectStatut::QF, $prospect->refresh()->statut);
    }

    /**
     * @return array{0: User, 1: Prospect}
     */
    private function readyProspectForQf(): array
    {
        CrmProfile::create([
            'role_name' => 'team_leader',
            'label' => 'Team Leader',
            'panels' => ['ns-conseil'],
            'can_validate_qf' => true,
            'actif' => true,
        ]);

        $teamLeader = User::factory()->create([
            'role_cache' => 'team_leader',
        ]);

        $commercial = User::factory()->create([
            'role_cache' => 'commercial',
        ]);

        $prospect = Prospect::create([
            'nom' => 'CSE Demo',
            'raison_sociale' => 'CSE Demo',
            'secteur_activite' => 'Industrie',
            'nb_salaries' => 80,
            'telephone' => '06 12 34 56 78',
            'departement' => '45',
            'ville' => 'Orleans',
            'interlocuteur_nom' => 'Marie Martin',
            'interlocuteur_email' => 'marie@example.test',
            'commercial_id' => $commercial->id,
        ]);

        return [$teamLeader, $prospect];
    }

    private function createValidRdv(Prospect $prospect): RendezVous
    {
        return RendezVous::create([
            'rdvable_type' => Prospect::class,
            'rdvable_id' => $prospect->id,
            'commercial_id' => $prospect->commercial_id,
            'type' => \App\Enums\RendezVousType::Presentation,
            'statut' => \App\Enums\RendezVousStatut::Planifie,
            'date_heure' => now()->addDay(),
            'lieu' => 'Visio',
            'interlocuteur_nom' => 'Marie Martin',
            'interlocuteur_email' => 'marie@example.test',
            'pdf_recap' => 'fiches/rdv-demo.pdf',
            'email_confirmation_envoye' => true,
            'email_invitation_envoye' => true,
        ]);
    }
}
