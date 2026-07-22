<?php

namespace Tests\Feature\Models;

use App\Enums\CorpsDeMetier;
use App\Enums\StatutCompteArtisan;
use App\Models\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ArtisanFeatureTest extends TestCase
{
    use RefreshDatabase;

    private static int $siretCounter = 0;

    private function createArtisan(array $overrides = []): Artisan
    {
        self::$siretCounter++;
        $siret = str_pad((string) self::$siretCounter, 14, '0', STR_PAD_LEFT);

        return Artisan::create(array_merge([
            'nom' => 'Martin',
            'prenom' => 'Pierre',
            'corps_de_metier' => CorpsDeMetier::Plomberie,
            'telephone_principal' => '0612345678',
            'email' => 'martin@test.com',
            'siret' => $siret,
            'zone_intervention' => 'Paris et Île-de-France',
        ], $overrides));
    }

    #[Test]
    public function default_values_on_creation(): void
    {
        $artisan = $this->createArtisan();

        $this->assertEquals(StatutCompteArtisan::EnAttenteActivation, $artisan->statut_compte);
        $this->assertNotNull($artisan->date_souscription);
        $this->assertNotNull($artisan->canal_alerte);
    }

    #[Test]
    public function activer_artisan(): void
    {
        $artisan = $this->createArtisan();
        $artisan->activer();

        $fresh = $artisan->fresh();
        $this->assertEquals(StatutCompteArtisan::Actif, $fresh->statut_compte);
        $this->assertNotNull($fresh->date_activation);
    }

    #[Test]
    public function suspendre_artisan(): void
    {
        $artisan = $this->createArtisan(['statut_compte' => StatutCompteArtisan::Actif]);
        $artisan->suspendre('Motif test');

        $fresh = $artisan->fresh();
        $this->assertEquals(StatutCompteArtisan::Suspendu, $fresh->statut_compte);
        $this->assertStringContainsString('Motif test', $fresh->notes);
    }

    #[Test]
    public function reactiver_artisan(): void
    {
        $artisan = $this->createArtisan(['statut_compte' => StatutCompteArtisan::Suspendu]);
        $artisan->reactiver();

        $this->assertEquals(StatutCompteArtisan::Actif, $artisan->fresh()->statut_compte);
    }

    #[Test]
    public function date_activation_set_on_status_change_to_actif(): void
    {
        $artisan = $this->createArtisan();
        $this->assertNull($artisan->date_activation);

        $artisan->update(['statut_compte' => StatutCompteArtisan::Actif]);

        $this->assertNotNull($artisan->fresh()->date_activation);
    }

    #[Test]
    public function scope_actifs(): void
    {
        $this->createArtisan(['email' => 'active@test.com', 'statut_compte' => StatutCompteArtisan::Actif]);
        $this->createArtisan(['email' => 'pending@test.com']);

        $this->assertCount(1, Artisan::actifs()->get());
    }

    #[Test]
    public function scope_en_attente(): void
    {
        $this->createArtisan(['email' => 'pending@test.com']);
        $this->createArtisan(['email' => 'active@test.com', 'statut_compte' => StatutCompteArtisan::Actif]);

        $this->assertCount(1, Artisan::enAttente()->get());
    }

    #[Test]
    public function scope_suspendus(): void
    {
        $this->createArtisan(['email' => 'suspended@test.com', 'statut_compte' => StatutCompteArtisan::Suspendu]);
        $this->createArtisan(['email' => 'active@test.com', 'statut_compte' => StatutCompteArtisan::Actif]);

        $this->assertCount(1, Artisan::suspendus()->get());
    }

    #[Test]
    public function scope_by_metier(): void
    {
        $this->createArtisan(['email' => 'plombier@test.com', 'corps_de_metier' => CorpsDeMetier::Plomberie]);
        $this->createArtisan(['email' => 'elec@test.com', 'corps_de_metier' => CorpsDeMetier::Electricite]);

        $this->assertCount(1, Artisan::byMetier(CorpsDeMetier::Plomberie)->get());
    }

    #[Test]
    public function scope_prioritaires(): void
    {
        $this->createArtisan(['email' => 'plombier@test.com', 'corps_de_metier' => CorpsDeMetier::Plomberie]);
        $this->createArtisan(['email' => 'peintre@test.com', 'corps_de_metier' => CorpsDeMetier::Peinture]);

        $this->assertCount(1, Artisan::prioritaires()->get());
    }

    #[Test]
    public function scope_disponibles(): void
    {
        $this->createArtisan([
            'email' => 'dispo@test.com',
            'statut_compte' => StatutCompteArtisan::Actif,
            'agenda_disponibilites' => true,
        ]);
        $this->createArtisan([
            'email' => 'nondispo@test.com',
            'statut_compte' => StatutCompteArtisan::Actif,
            'agenda_disponibilites' => false,
        ]);

        $this->assertCount(1, Artisan::disponibles()->get());
    }

    #[Test]
    public function scope_bien_notes(): void
    {
        $this->createArtisan(['email' => 'good@test.com', 'note_moyenne' => 4.5]);
        $this->createArtisan(['email' => 'bad@test.com', 'note_moyenne' => 3.0]);

        $this->assertCount(1, Artisan::bienNotes()->get());
        $this->assertCount(2, Artisan::bienNotes(3.0)->get());
    }

    #[Test]
    public function soft_deletes(): void
    {
        $artisan = $this->createArtisan();
        $artisan->delete();

        $this->assertSoftDeleted($artisan);
    }
}
