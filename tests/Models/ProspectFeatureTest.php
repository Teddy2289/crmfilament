<?php

namespace Tests\Feature\Models;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProspectFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createProspect(array $overrides = []): Prospect
    {
        return Prospect::create(array_merge([
            'nom' => 'Entreprise Test',
            'statut' => ProspectStatut::AC,
            'telephone' => '0612345678',
            'email' => 'contact@test.com',
        ], $overrides));
    }

    #[Test]
    public function changer_statut(): void
    {
        $prospect = $this->createProspect();
        $prospect->changerStatut(ProspectStatut::STD_NR, 'Test note');

        $this->assertEquals(ProspectStatut::STD_NR, $prospect->fresh()->statut);
    }

    #[Test]
    public function changer_statut_to_ko_requires_notes(): void
    {
        $prospect = $this->createProspect();

        $this->expectException(\Exception::class);
        $prospect->changerStatut(ProspectStatut::KO);
    }

    #[Test]
    public function changer_statut_to_ko_with_motif(): void
    {
        $prospect = $this->createProspect();
        $prospect->changerStatut(ProspectStatut::KO, 'Non intéressé');

        $fresh = $prospect->fresh();
        $this->assertEquals(ProspectStatut::KO, $fresh->statut);
        $this->assertEquals('Non intéressé', $fresh->motif_ko);
    }

    #[Test]
    public function qualifier_prospect(): void
    {
        $prospect = $this->createProspect();
        $prospect->qualifier();

        $this->assertEquals(ProspectStatut::QF, $prospect->fresh()->statut);
    }

    #[Test]
    public function marquer_ko(): void
    {
        $prospect = $this->createProspect();
        $prospect->marquerKO('Pas intéressé');

        $this->assertEquals(ProspectStatut::KO, $prospect->fresh()->statut);
    }

    #[Test]
    public function marquer_reponse_positive(): void
    {
        $prospect = $this->createProspect();
        $prospect->marquerReponsePositive();

        $this->assertEquals(ProspectStatut::RP, $prospect->fresh()->statut);
    }

    #[Test]
    public function marquer_reponse_positive_cse(): void
    {
        $prospect = $this->createProspect();
        $prospect->marquerReponsePositiveCSE();

        $this->assertEquals(ProspectStatut::RPC, $prospect->fresh()->statut);
    }

    #[Test]
    public function standard_joint(): void
    {
        $prospect = $this->createProspect();
        $prospect->standardJoint();

        $this->assertEquals(ProspectStatut::STD_Joint, $prospect->fresh()->statut);
    }

    #[Test]
    public function programmer_rappel(): void
    {
        $prospect = $this->createProspect(['statut' => ProspectStatut::STD_NR]);
        $date = now()->addDays(3);

        $prospect->programmerRappel($date);

        $fresh = $prospect->fresh();
        $this->assertNotNull($fresh->rappel_planifie_at);
    }

    #[Test]
    public function annuler_rappel(): void
    {
        $prospect = $this->createProspect(['rappel_planifie_at' => now()->addDay()]);
        $prospect->annulerRappel();

        $this->assertNull($prospect->fresh()->rappel_planifie_at);
    }

    #[Test]
    public function valider_qf(): void
    {
        $user = User::create([
            'nom' => 'Admin',
            'prenom' => 'Test',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'actif' => true,
        ]);

        $prospect = $this->createProspect();
        $prospect->validerQF($user->id);

        $fresh = $prospect->fresh();
        $this->assertTrue($fresh->qf_valide);
        $this->assertEquals($user->id, $fresh->valide_par);
        $this->assertNotNull($fresh->qf_valide_at);
    }

    #[Test]
    public function assigner_teleprospecteur(): void
    {
        $user = User::create([
            'nom' => 'TP',
            'prenom' => 'Test',
            'email' => 'tp@test.com',
            'password' => bcrypt('password'),
            'actif' => true,
        ]);

        $prospect = $this->createProspect();
        $prospect->assignerTeleprospecteur($user->id);

        $this->assertEquals($user->id, $prospect->fresh()->teleprospecteur_id);
    }

    #[Test]
    public function assigner_commercial(): void
    {
        $user = User::create([
            'nom' => 'Com',
            'prenom' => 'Test',
            'email' => 'com@test.com',
            'password' => bcrypt('password'),
            'actif' => true,
        ]);

        $prospect = $this->createProspect();
        $prospect->assignerCommercial($user->id);

        $this->assertEquals($user->id, $prospect->fresh()->commercial_id);
    }

    #[Test]
    public function ajouter_note(): void
    {
        $prospect = $this->createProspect();
        $prospect->ajouterNote('Note de test');

        $this->assertStringContainsString('Note de test', $prospect->fresh()->description);
    }

    #[Test]
    public function marquer_contact(): void
    {
        $prospect = $this->createProspect(['date_premier_contact' => null]);
        $prospect->marquerContact();

        $this->assertNotNull($prospect->fresh()->date_premier_contact);
    }

    #[Test]
    public function marquer_contact_does_not_overwrite_existing_date(): void
    {
        $originalDate = now()->subDays(5);
        $prospect = $this->createProspect(['date_premier_contact' => $originalDate]);
        $prospect->marquerContact();

        $this->assertEquals(
            $originalDate->toDateString(),
            $prospect->fresh()->date_premier_contact->toDateString()
        );
    }

    #[Test]
    public function mettre_a_jour_contact(): void
    {
        $prospect = $this->createProspect();
        $prospect->mettreAJourContact('Nouveau Nom', 'DRH', '0698765432', 'drh@test.com');

        $fresh = $prospect->fresh();
        $this->assertEquals('Nouveau Nom', $fresh->interlocuteur_nom);
        $this->assertEquals('DRH', $fresh->interlocuteur_fonction);
    }

    #[Test]
    public function soft_deletes(): void
    {
        $prospect = $this->createProspect();
        $prospect->delete();

        $this->assertSoftDeleted($prospect);
    }
}
