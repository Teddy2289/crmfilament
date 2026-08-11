<?php

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Models\ContactPartenaire;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Services\ProspectConversionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it converts a qualified prospect to partner', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::QF,
        'qf_valide' => true,
        'nom' => 'Test Company',
        'type_pressenti' => OrganizationType::CSE->value,
        'siret' => '12345678901234',
        'telephone' => '0123456789',
        'email' => 'test@example.com',
        'ville' => 'Paris',
        'departement' => '75',
        'dirigeant_nom' => 'Dupont',
        'dirigeant_prenom' => 'Jean',
        'dirigeant_fonction' => 'PDG',
    ]);

    $service = new ProspectConversionService();
    $partenaire = $service->convertProspectToPartenaire($prospect);

    expect($partenaire)
        ->toBeInstanceOf(Partenaire::class)
        ->and($partenaire->nom)->toBe('Test Company')
        ->and($partenaire->type)->toBe(OrganizationType::CSE->value)
        ->and($partenaire->siret)->toBe('12345678901234')
        ->and($partenaire->statut)->toBe(OrganizationStatus::SigneAccordCadre)
        ->and($partenaire->prospect_id)->toBe($prospect->id);

    // Check that the prospect was soft-deleted and has the conversion reference
    $prospect->refresh();
    expect($prospect->converti_partenaire_id)->toBe($partenaire->id);
    expect($prospect->trashed())->toBeTrue();

    // Check that the dirigeant was migrated
    $contact = ContactPartenaire::where('partenaire_id', $partenaire->id)
        ->where('nom', 'Dupont')
        ->where('prenom', 'Jean')
        ->first();

    expect($contact)->not->toBeNull()
        ->and($contact->fonction)->toBe('PDG')
        ->and($contact->est_principal)->toBeTrue()
        ->and($contact->est_decisionnaire)->toBeTrue();
});

test('it throws exception when prospect is not convertible', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::AC,
        'qf_valide' => false,
    ]);

    $service = new ProspectConversionService();

    expect(fn () => $service->convertProspectToPartenaire($prospect))
        ->toThrow(\Exception::class, 'Seuls les prospects QF validés et non déjà convertis peuvent être convertis en partenaire.');
});

test('it migrates CSE secretary contact', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::QF,
        'qf_valide' => true,
        'type_pressenti' => OrganizationType::CSE->value,
        'cse_secretaire_nom' => 'Martin',
        'cse_secretaire_prenom' => 'Marie',
        'cse_secretaire_email_pro' => 'marie@example.com',
        'cse_secretaire_tel_direct' => '0123456789',
    ]);

    $service = new ProspectConversionService();
    $partenaire = $service->convertProspectToPartenaire($prospect);

    $contact = ContactPartenaire::where('partenaire_id', $partenaire->id)
        ->where('nom', 'Martin')
        ->where('prenom', 'Marie')
        ->first();

    expect($contact)->not->toBeNull()
        ->and($contact->fonction)->toBe('Secrétaire CSE')
        ->and($contact->email)->toBe('marie@example.com')
        ->and($contact->telephone_direct)->toBe('0123456789');
});

test('it migrates CSE treasurer contact', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::QF,
        'qf_valide' => true,
        'type_pressenti' => OrganizationType::CSE->value,
        'cse_tresorier_nom' => 'Bernard',
        'cse_tresorier_prenom' => 'Pierre',
    ]);

    $service = new ProspectConversionService();
    $partenaire = $service->convertProspectToPartenaire($prospect);

    $contact = ContactPartenaire::where('partenaire_id', $partenaire->id)
        ->where('nom', 'Bernard')
        ->where('prenom', 'Pierre')
        ->first();

    expect($contact)->not->toBeNull()
        ->and($contact->fonction)->toBe('Trésorier CSE');
});

test('it migrates syndicat responsible contact', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::QF,
        'qf_valide' => true,
        'type_pressenti' => OrganizationType::Syndicat->value,
        'syndicat_responsable_nom' => 'Petit',
        'syndicat_responsable_prenom' => 'Luc',
        'syndicat_appartenance' => 'CFDT',
        'syndicat_responsable_fonction' => 'Délégué',
    ]);

    $service = new ProspectConversionService();
    $partenaire = $service->convertProspectToPartenaire($prospect);

    $contact = ContactPartenaire::where('partenaire_id', $partenaire->id)
        ->where('nom', 'Petit')
        ->where('prenom', 'Luc')
        ->first();

    expect($contact)->not->toBeNull()
        ->and($contact->fonction)->toBe('Délégué')
        ->and($contact->nom_syndicat)->toBe('CFDT');
});

test('it handles missing contact data gracefully', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::QF,
        'qf_valide' => true,
        'nom' => 'Simple Company',
    ]);

    $service = new ProspectConversionService();
    $partenaire = $service->convertProspectToPartenaire($prospect);

    expect($partenaire)->toBeInstanceOf(Partenaire::class);
    expect(ContactPartenaire::where('partenaire_id', $partenaire->id)->count())->toBe(0);
});

test('it rolls back transaction on failure', function () {
    $prospect = Prospect::factory()->create([
        'statut' => ProspectStatut::QF,
        'qf_valide' => true,
        'nom' => 'Test Company',
    ]);

    // Mock the Partenaire::create to throw an exception
    Partenaire::spy();
    Partenaire::shouldReceive('create')->andThrow(new \Exception('Database error'));

    $service = new ProspectConversionService();

    expect(fn () => $service->convertProspectToPartenaire($prospect))
        ->toThrow(\Exception::class);

    // Verify the prospect was not modified
    $prospect->refresh();
    expect($prospect->converti_partenaire_id)->toBeNull();
    expect($prospect->trashed())->toBeFalse();
});
