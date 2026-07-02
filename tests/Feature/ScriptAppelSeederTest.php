<?php

namespace Tests\Feature;

use App\Models\ScriptAppel;
use Database\Seeders\ScriptAppelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ScriptAppelSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_seeds_ns_conseil_call_scripts_without_duplicates(): void
    {
        $this->seed(ScriptAppelSeeder::class);
        $this->seed(ScriptAppelSeeder::class);

        $this->assertSame(15, ScriptAppel::count());

        foreach (['prospect', 'partenaire', 'client'] as $typeContact) {
            foreach (array_keys(ScriptAppel::ONGLETS) as $onglet) {
                $this->assertDatabaseHas('scripts_appel', [
                    'type_contact' => $typeContact,
                    'onglet' => $onglet,
                    'actif' => true,
                    'deleted_at' => null,
                ]);
            }
        }
    }

    #[Test]
    public function prospect_phoning_workflow_can_load_cse_scripts_by_tab(): void
    {
        $this->seed(ScriptAppelSeeder::class);

        $scripts = ScriptAppel::parOngletPourContact('prospect');

        $this->assertSame(array_keys(ScriptAppel::ONGLETS), array_keys($scripts));
        $this->assertSame('ns-conseil-prospect-cse-accroche', $scripts['accroche']?->slug);
        $this->assertSame('ns-conseil-prospect-cse-objections', $scripts['objections']?->slug);
        $this->assertStringContainsString('DEP_XX', $scripts['closing']?->contenu);
    }
}
