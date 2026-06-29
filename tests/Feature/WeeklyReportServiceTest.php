<?php

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Enums\RendezVousStatut;
use App\Jobs\SendWeeklyReportJob;
use App\Mail\WeeklyReportMail;
use App\Models\Appel;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\Crm\WeeklyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WeeklyReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    #[Test]
    public function teleprospecteur_report_has_stable_counts_and_reminder_keys(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));

        $teleprospecteur = $this->user('teleprospecteur@test.com', User::ROLE_TELEPROSPECTEUR);
        $prospect = Prospect::create([
            'nom' => 'CSE Test',
            'statut' => ProspectStatut::AC->value,
            'telephone' => '0611111111',
            'email' => 'cse@test.com',
            'teleprospecteur_id' => $teleprospecteur->id,
            'rappel_planifie_at' => CarbonImmutable::now()->addDay(),
        ]);

        Appel::create([
            'appelable_type' => Prospect::class,
            'appelable_id' => $prospect->id,
            'user_id' => $teleprospecteur->id,
            'phoning_agent_id' => $teleprospecteur->id,
            'phoning_status' => ProspectStatut::RPC->value,
            'date_heure' => CarbonImmutable::now()->subWeek()->addDay(),
        ]);

        $rapport = app(WeeklyReportService::class)->pourTeleprospecteur($teleprospecteur);

        $this->assertSame(User::ROLE_TELEPROSPECTEUR, $rapport['role']);
        $this->assertSame(1, $rapport['appels_semaine']);
        $this->assertSame(1, $rapport['cse_joints']);
        $this->assertSame(100.0, $rapport['taux_conversion']);
        $this->assertSame(1, $rapport['base_ac']);
        $this->assertSame(1, $rapport['rappels_a_venir']);
        $this->assertCount(1, $rapport['prochains_rappels']);
        $this->assertNotEmpty($rapport['prospects_par_statut']);
    }

    #[Test]
    public function commercial_and_team_leader_reports_render_in_weekly_mail(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));

        $commercial = $this->user('commercial@test.com', User::ROLE_COMMERCIAL);
        $teamLeader = $this->user('team@test.com', WeeklyReportService::ROLE_TEAM_LEADER);
        $prospect = Prospect::create([
            'nom' => 'Prospect RDV',
            'statut' => ProspectStatut::RPC->value,
            'telephone' => '0622222222',
            'email' => 'rdv@test.com',
            'commercial_id' => $commercial->id,
        ]);
        $partenaire = Partenaire::create([
            'nom' => 'Partenaire Actif',
            'type' => OrganizationType::CSE->value,
            'statut' => OrganizationStatus::AProspecter->value,
            'commercial_id' => $commercial->id,
        ]);

        RendezVous::create([
            'rdvable_type' => Prospect::class,
            'rdvable_id' => $prospect->id,
            'commercial_id' => $commercial->id,
            'statut' => RendezVousStatut::Realise->value,
            'date_heure' => CarbonImmutable::now()->subWeek()->addDay(),
            'interlocuteur_nom' => 'Interlocuteur',
        ]);
        RendezVous::create([
            'rdvable_type' => Partenaire::class,
            'rdvable_id' => $partenaire->id,
            'commercial_id' => $commercial->id,
            'statut' => RendezVousStatut::Planifie->value,
            'date_heure' => CarbonImmutable::now()->addDay(),
            'interlocuteur_nom' => 'Partenaire',
        ]);

        $service = app(WeeklyReportService::class);
        $rapportCommercial = $service->pourCommercial($commercial);
        $rapportTeamLeader = $service->pourTeamLeader($teamLeader);

        $this->assertSame(1, $rapportCommercial['rdv_total']);
        $this->assertSame(1, $rapportCommercial['rdv_realises']);
        $this->assertSame(1, $rapportCommercial['rdv_a_venir']->count());
        $this->assertSame(1, $rapportCommercial['prospects_en_attente']);

        $this->assertStringContainsString('Rapport hebdomadaire CRM', (new WeeklyReportMail($rapportCommercial))->render());
        $this->assertStringContainsString('Rapport hebdomadaire CRM', (new WeeklyReportMail($rapportTeamLeader))->render());
    }

    #[Test]
    public function weekly_report_job_sends_one_mail_per_active_role_recipient(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-06-29 09:00:00'));
        Mail::fake();

        $this->user('tp@test.com', User::ROLE_TELEPROSPECTEUR);
        $this->user('commercial@test.com', User::ROLE_COMMERCIAL);
        $this->user('superviseur@test.com', User::ROLE_SUPERVISEUR);
        $this->user('team@test.com', WeeklyReportService::ROLE_TEAM_LEADER);
        $this->user('inactive@test.com', User::ROLE_COMMERCIAL, false);

        $envoyes = (new SendWeeklyReportJob([
            User::ROLE_TELEPROSPECTEUR,
            User::ROLE_COMMERCIAL,
            User::ROLE_SUPERVISEUR,
            WeeklyReportService::ROLE_TEAM_LEADER,
        ]))->handle(app(WeeklyReportService::class));

        $this->assertSame(4, $envoyes);
        Mail::assertSent(WeeklyReportMail::class, 4);
    }

    private function user(string $email, string $role, bool $actif = true): User
    {
        return User::create([
            'nom' => 'Test',
            'prenom' => ucfirst(strtok($email, '@')),
            'email' => $email,
            'password' => bcrypt('password'),
            'role_cache' => $role,
            'actif' => $actif,
        ]);
    }
}
