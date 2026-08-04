<?php

namespace Tests\Unit;

use App\Models\HistoriqueModification;
use App\Models\Partenaire;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoriqueModificationFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_enregistrer_creation_uses_existing_user_when_no_auth_context_is_available(): void
    {
        $user = User::factory()->create();

        auth()->logout();

        $partenaire = Partenaire::factory()->create([
            'nom' => 'Partenaire fallback',
            'type' => 'Entreprise directe',
            'statut' => 'en_cours_prospection',
            'email' => 'fallback@example.com',
        ]);

        $historique = HistoriqueModification::enregistrerCreation($partenaire);

        $this->assertSame($user->id, $historique->user_id);
    }
}
