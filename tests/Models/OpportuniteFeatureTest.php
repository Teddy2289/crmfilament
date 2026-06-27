<?php

namespace Tests\Feature\Models;

use App\Models\Opportunite;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpportuniteFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function createOpportunite(array $overrides = []): Opportunite
    {
        return Opportunite::create(array_merge([
            'nom_entite' => 'Société Test',
            'statut' => 'nouveau',
            'potentiel' => 'moyen',
            'source_detection' => 'reseau_commercial',
            'telephone' => '0612345678',
            'email' => 'contact@test.com',
            'date_detection' => now(),
        ], $overrides));
    }

    #[Test]
    public function contacter_changes_status(): void
    {
        $opp = $this->createOpportunite();
        $opp->contacter();

        $fresh = $opp->fresh();
        $this->assertEquals('en_cours_evaluation', $fresh->statut);
        $this->assertNotNull($fresh->date_premier_contact);
    }

    #[Test]
    public function planifier_rdv(): void
    {
        $opp = $this->createOpportunite();
        $opp->planifierRDV();

        $this->assertEquals('qualifiee', $opp->fresh()->statut);
    }

    #[Test]
    public function demarrer_negociation(): void
    {
        $opp = $this->createOpportunite();
        $opp->demarrerNegociation();

        $this->assertEquals('qualifiee', $opp->fresh()->statut);
    }

    #[Test]
    public function marquer_qualifiee(): void
    {
        $opp = $this->createOpportunite();
        $opp->marquerQualifiee();

        $this->assertEquals('qualifiee', $opp->fresh()->statut);
    }

    #[Test]
    public function convertir_en_prospect(): void
    {
        $opp = $this->createOpportunite([
            'nom_entite' => 'Entreprise ABC',
            'telephone' => '0612345678',
            'email' => 'abc@test.com',
            'departement' => '75',
            'source_detection' => 'parrainage',
            'details_source' => 'Recommande par Client Test',
            'notes' => 'Contexte initial',
            'statut' => 'qualifiee',
        ]);

        $prospect = $opp->convertirEnProspect();

        $this->assertNotNull($prospect);
        $this->assertInstanceOf(Prospect::class, $prospect);
        $this->assertEquals('Entreprise ABC', $prospect->nom);
        $this->assertEquals('0612345678', $prospect->telephone);
        $this->assertStringContainsString('Source detection: Parrainage / recommandation', $prospect->description);
        $this->assertStringContainsString('Details source: Recommande par Client Test', $prospect->description);
        $this->assertStringContainsString('Contexte initial', $prospect->description);
        $this->assertEquals('converti', $opp->fresh()->statut);
        $this->assertEquals($prospect->id, $opp->fresh()->converti_en_prospect_id);
    }

    #[Test]
    public function convertir_en_prospect_requires_qualified_status(): void
    {
        $opp = $this->createOpportunite();

        $this->expectException(\LogicException::class);

        $opp->convertirEnProspect();
    }

    #[Test]
    public function marquer_perdue(): void
    {
        $opp = $this->createOpportunite();
        $opp->marquerPerdue('Budget insuffisant');

        $fresh = $opp->fresh();
        $this->assertEquals('perdu', $fresh->statut);
        $this->assertEquals('Budget insuffisant', $fresh->raison_perte);
    }

    #[Test]
    public function marquer_perdue_requires_reason(): void
    {
        $opp = $this->createOpportunite();

        $this->expectException(\InvalidArgumentException::class);

        $opp->marquerPerdue(' ');
    }

    #[Test]
    public function assigner_a_user(): void
    {
        $user = User::create([
            'nom' => 'Com',
            'prenom' => 'Test',
            'email' => 'com@test.com',
            'password' => bcrypt('password'),
            'actif' => true,
        ]);

        $opp = $this->createOpportunite();
        $opp->assigner($user->id);

        $this->assertEquals($user->id, $opp->fresh()->assigne_a);
    }

    #[Test]
    public function qualifier_with_valid_statut(): void
    {
        $opp = $this->createOpportunite();
        $opp->qualifier('en_cours_evaluation');

        $this->assertEquals('en_cours_evaluation', $opp->fresh()->statut);
    }

    #[Test]
    public function qualifier_with_invalid_statut_throws(): void
    {
        $opp = $this->createOpportunite();

        $this->expectException(\InvalidArgumentException::class);
        $opp->qualifier('statut_invalide');
    }

    #[Test]
    public function cdc_status_options_do_not_expose_legacy_statuses(): void
    {
        $this->assertSame([
            'nouveau',
            'en_cours_evaluation',
            'qualifiee',
            'converti',
            'perdu',
        ], array_keys(Opportunite::statutsPourSelect()));

        $this->assertArrayNotHasKey('rdv_planifie', Opportunite::statutsPourSelect());
        $this->assertArrayNotHasKey('en_negociation', Opportunite::statutsPourSelect());
    }

    #[Test]
    public function ajouter_note(): void
    {
        $opp = $this->createOpportunite();
        $opp->ajouterNote('Note importante');

        $this->assertStringContainsString('Note importante', $opp->fresh()->notes);
    }

    #[Test]
    public function scope_actives(): void
    {
        $this->createOpportunite(['email' => 'active@test.com', 'statut' => 'nouveau']);
        $this->createOpportunite(['email' => 'converted@test.com', 'statut' => 'converti']);
        $this->createOpportunite(['email' => 'lost@test.com', 'statut' => 'perdu']);

        $this->assertCount(1, Opportunite::actives()->get());
    }

    #[Test]
    public function scope_converties(): void
    {
        $this->createOpportunite(['email' => 'active@test.com', 'statut' => 'nouveau']);
        $this->createOpportunite(['email' => 'converted@test.com', 'statut' => 'converti']);

        $this->assertCount(1, Opportunite::converties()->get());
    }

    #[Test]
    public function scope_perdues(): void
    {
        $this->createOpportunite(['email' => 'active@test.com', 'statut' => 'nouveau']);
        $this->createOpportunite(['email' => 'lost@test.com', 'statut' => 'perdu']);

        $this->assertCount(1, Opportunite::perdues()->get());
    }

    #[Test]
    public function scope_nouvelles(): void
    {
        $this->createOpportunite(['email' => 'new@test.com', 'statut' => 'nouveau']);
        $this->createOpportunite(['email' => 'contact@test2.com', 'statut' => 'contacte']);

        $this->assertCount(1, Opportunite::nouvelles()->get());
    }

    #[Test]
    public function scope_potentiel_eleve(): void
    {
        $this->createOpportunite(['email' => 'high@test.com', 'potentiel' => 'eleve']);
        $this->createOpportunite(['email' => 'vhigh@test.com', 'potentiel' => 'tres_eleve']);
        $this->createOpportunite(['email' => 'low@test.com', 'potentiel' => 'faible']);

        $this->assertCount(2, Opportunite::potentielEleve()->get());
    }

    #[Test]
    public function scope_non_assignees(): void
    {
        $this->createOpportunite(['email' => 'unassigned@test.com']);

        $this->assertCount(1, Opportunite::nonAssignees()->get());
    }

    #[Test]
    public function soft_deletes(): void
    {
        $opp = $this->createOpportunite();
        $opp->delete();

        $this->assertSoftDeleted($opp);
    }
}
