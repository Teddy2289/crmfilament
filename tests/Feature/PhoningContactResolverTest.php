<?php

namespace Tests\Feature;

use App\Models\ContactPartenaire;
use App\Models\Partenaire;
use App\Services\Phoning\PhoningContactResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoningContactResolverTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function partenaire_type_falls_back_to_partenaire_when_contact_partenaire_is_missing(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Partenaire de test',
            'type' => 'CSE',
            'statut' => 'a_prospecter',
        ]);
        $resolver = app(PhoningContactResolver::class);

        $resolved = $resolver->resolveModel('partenaire', $partenaire->id);

        $this->assertInstanceOf(Partenaire::class, $resolved);
        $this->assertTrue($partenaire->is($resolved));
    }

    #[Test]
    public function partenaire_type_prefers_contact_partenaire_when_both_models_share_the_id(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Partenaire de test',
            'type' => 'CSE',
            'statut' => 'a_prospecter',
        ]);
        $contact = ContactPartenaire::create([
            'partenaire_id' => $partenaire->id,
            'nom' => 'Contact de test',
        ]);
        $resolver = app(PhoningContactResolver::class);

        $resolved = $resolver->resolveModel('partenaire', $contact->id);

        $this->assertInstanceOf(ContactPartenaire::class, $resolved);
        $this->assertTrue($contact->is($resolved));
    }
}

