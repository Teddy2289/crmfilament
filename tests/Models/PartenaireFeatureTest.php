<?php

namespace Tests\Feature\Models;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\ClientResource\Import\BaseClientImporter;
use App\Models\Client;
use App\Models\Partenaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PartenaireFeatureTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function nomenclature_interne_is_generated_from_type_enterprise_and_city(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Leroy Merlin La Rochelle',
            'entreprise' => 'Leroy Merlin',
            'ville' => 'La Rochelle',
            'type' => OrganizationType::CSE->value,
            'statut' => OrganizationStatus::AProspecter->value,
        ]);

        $this->assertSame('CSE Leroy Merlin La Rochelle', $partenaire->fresh()->nomenclature_interne);
        $this->assertSame('CSE Leroy Merlin La Rochelle', $partenaire->fresh()->nomenclature_suggeree);
    }

    #[Test]
    public function nomenclature_uses_public_name_when_enterprise_is_missing(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Association Alpha',
            'ville' => 'Paris',
            'type' => OrganizationType::Association->value,
            'statut' => OrganizationStatus::AProspecter->value,
        ]);

        $this->assertSame('Association Association Alpha Paris', $partenaire->fresh()->nomenclature_interne);
    }

    #[Test]
    public function nomenclature_interne_is_recalculated_on_partner_updates(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Leroy Merlin La Rochelle',
            'entreprise' => 'Leroy Merlin',
            'ville' => 'La Rochelle',
            'type' => OrganizationType::CSE->value,
            'statut' => OrganizationStatus::AProspecter->value,
        ]);

        $partenaire->update([
            'ville' => 'Nantes',
            'nomenclature_interne' => 'ancienne valeur',
        ]);

        $this->assertSame('CSE Leroy Merlin Nantes', $partenaire->fresh()->nomenclature_interne);
    }

    #[Test]
    public function client_import_links_partner_by_exact_nomenclature(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Leroy Merlin La Rochelle',
            'entreprise' => 'Leroy Merlin',
            'ville' => 'La Rochelle',
            'type' => OrganizationType::CSE->value,
            'statut' => OrganizationStatus::AProspecter->value,
        ]);

        $importer = new class extends BaseClientImporter
        {
            public static function getName(): string
            {
                return 'Test clients';
            }

            public static function getRequiredColumns(): array
            {
                return [];
            }

            protected function mapRow(array $row): array
            {
                return [
                    'client' => [
                        'nom_tiers' => 'Client Test',
                        'email' => 'client@example.test',
                        '_partenaire_nomenclature' => $row['partenaire_nomenclature'] ?? null,
                        'extra_data' => ['source_import' => 'test'],
                    ],
                    'dossier' => [
                        'ref_client' => 'DOS-001',
                        'intitule_programme' => 'Excel',
                    ],
                    'heures' => [],
                    'planning' => [],
                    'parrain' => [],
                ];
            }
        };

        $result = $importer->import([
            ['partenaire_nomenclature' => 'CSE Leroy Merlin La Rochelle'],
        ]);

        $client = Client::query()->firstOrFail();

        $this->assertSame([], $result['errors']);
        $this->assertSame($partenaire->id, $client->partenaire_id);
        $this->assertSame('test', $client->extra_data['source_import']);
        $this->assertSame('rattache', $client->extra_data['partenaire_import']['statut']);
        $this->assertSame('CSE Leroy Merlin La Rochelle', $client->extra_data['partenaire_import']['nomenclature']);
    }

    #[Test]
    public function partner_can_be_found_by_generated_nomenclature(): void
    {
        $partenaire = Partenaire::create([
            'nom' => 'Leroy Merlin La Rochelle',
            'entreprise' => 'Leroy Merlin',
            'ville' => 'La Rochelle',
            'type' => OrganizationType::CSE->value,
            'statut' => OrganizationStatus::AProspecter->value,
        ]);

        $match = Partenaire::query()
            ->where('nomenclature_interne', 'CSE Leroy Merlin La Rochelle')
            ->first();

        $this->assertTrue($partenaire->is($match));
    }
}
