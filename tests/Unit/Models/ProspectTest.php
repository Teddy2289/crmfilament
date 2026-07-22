<?php

namespace Tests\Unit\Models;

use App\Enums\ProspectStatut;
use App\Models\Prospect;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ProspectTest extends TestCase
{
    #[Test]
    public function statut_label_attribute(): void
    {
        $prospect = new Prospect;
        $prospect->statut = ProspectStatut::AC;

        $this->assertEquals('À contacter', $prospect->statut_label);
    }

    #[Test]
    public function statut_color_attribute(): void
    {
        $prospect = new Prospect;
        $prospect->statut = ProspectStatut::KO;

        $this->assertEquals('danger', $prospect->statut_color);
    }

    #[Test]
    public function statut_icon_attribute(): void
    {
        $prospect = new Prospect;
        $prospect->statut = ProspectStatut::QF;

        $this->assertEquals('heroicon-o-check-circle', $prospect->statut_icon);
    }

    #[Test]
    public function statut_description_attribute(): void
    {
        $prospect = new Prospect;

        $prospect->statut = ProspectStatut::AC;
        $this->assertStringContainsString('À contacter', $prospect->statut_description);

        $prospect->statut = ProspectStatut::QF;
        $this->assertStringContainsString('Qualifié', $prospect->statut_description);
    }

    #[Test]
    public function est_qualifie_only_for_qf(): void
    {
        $prospect = new Prospect;

        $prospect->statut = ProspectStatut::QF;
        $this->assertTrue($prospect->est_qualifie);

        $prospect->statut = ProspectStatut::AC;
        $this->assertFalse($prospect->est_qualifie);
    }

    #[Test]
    public function est_a_planifier_for_rp_and_rpc(): void
    {
        $prospect = new Prospect;

        $prospect->statut = ProspectStatut::RP;
        $this->assertTrue($prospect->est_a_planifier);

        $prospect->statut = ProspectStatut::RPC;
        $this->assertTrue($prospect->est_a_planifier);

        $prospect->statut = ProspectStatut::AC;
        $this->assertFalse($prospect->est_a_planifier);
    }

    #[Test]
    public function est_a_relancer(): void
    {
        $prospect = new Prospect;

        $prospect->statut = ProspectStatut::AC;
        $this->assertTrue($prospect->est_a_relancer);

        $prospect->statut = ProspectStatut::STD_NR;
        $this->assertTrue($prospect->est_a_relancer);

        $prospect->statut = ProspectStatut::CSE_NR;
        $this->assertTrue($prospect->est_a_relancer);

        $prospect->statut = ProspectStatut::QF;
        $this->assertFalse($prospect->est_a_relancer);
    }

    #[Test]
    public function est_ko(): void
    {
        $prospect = new Prospect;

        $prospect->statut = ProspectStatut::KO;
        $this->assertTrue($prospect->est_KO);

        $prospect->statut = ProspectStatut::AC;
        $this->assertFalse($prospect->est_KO);
    }

    #[Test]
    public function adresse_complete_attribute(): void
    {
        $prospect = new Prospect;
        $prospect->adresse = '10 rue de la Paix';
        $prospect->code_postal = '75002';
        $prospect->ville = 'Paris';

        $this->assertEquals('10 rue de la Paix, 75002, Paris', $prospect->adresse_complete);
    }

    #[Test]
    public function adresse_complete_filters_empty_values(): void
    {
        $prospect = new Prospect;
        $prospect->adresse = null;
        $prospect->code_postal = '75002';
        $prospect->ville = 'Paris';

        $this->assertEquals('75002, Paris', $prospect->adresse_complete);
    }

    #[Test]
    public function interlocuteur_complet_attribute(): void
    {
        $prospect = new Prospect;
        $prospect->interlocuteur_nom = 'Dupont';
        $prospect->interlocuteur_fonction = 'Directeur';

        $this->assertEquals('Dupont - Directeur', $prospect->interlocuteur_complet);
    }

    #[Test]
    public function interlocuteur_complet_without_function(): void
    {
        $prospect = new Prospect;
        $prospect->interlocuteur_nom = 'Dupont';
        $prospect->interlocuteur_fonction = null;

        $this->assertEquals('Dupont', $prospect->interlocuteur_complet);
    }

    #[Test]
    public function interlocuteur_complet_without_name(): void
    {
        $prospect = new Prospect;
        $prospect->interlocuteur_nom = null;

        $this->assertEquals('Non défini', $prospect->interlocuteur_complet);
    }

    #[Test]
    public function taux_engagement_attribute(): void
    {
        $prospect = new Prospect;

        $prospect->statut = ProspectStatut::QF;
        $this->assertStringContainsString('⭐⭐⭐⭐⭐', $prospect->taux_engagement);

        $prospect->statut = ProspectStatut::RP;
        $engagement = $prospect->taux_engagement;
        $this->assertEquals(4, substr_count($engagement, '⭐'));

        $prospect->statut = ProspectStatut::STD_Joint;
        $engagement = $prospect->taux_engagement;
        $this->assertEquals(3, substr_count($engagement, '⭐'));

        $prospect->statut = ProspectStatut::AC;
        $engagement = $prospect->taux_engagement;
        $this->assertEquals(1, substr_count($engagement, '⭐'));
    }
}
