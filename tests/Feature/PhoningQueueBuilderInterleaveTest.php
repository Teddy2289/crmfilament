<?php

namespace Tests\Feature;

use App\Models\CampagnePhoning;
use App\Models\GroupeTelepro;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Phoning\PhoningQueueBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PhoningQueueBuilderInterleaveTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_default_queue_alternates_round_robin_across_the_users_campaigns(): void
    {
        $groupe = GroupeTelepro::create(['nom' => 'Groupe 44-45', 'actif' => true]);

        $telepro = User::factory()->create();
        $telepro->groupesTelepro()->attach($groupe->id);

        $campagneA = CampagnePhoning::create([
            'nom' => 'Campagne A',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC'], 'departement' => '44'],
        ]);

        $campagneB = CampagnePhoning::create([
            'nom' => 'Campagne B',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'groupe_telepro_id' => $groupe->id,
            'criteres' => ['statuts' => ['AC'], 'departement' => '45'],
        ]);

        // 3 prospects (dept 44) pour A, 2 (dept 45) pour B : des pools disjoints,
        // comme pour deux campagnes réelles ciblant des critères différents.
        // A doit continuer seule une fois B épuisée.
        $prospectsA = Prospect::factory()->count(3)->create([
            'statut' => 'AC',
            'departement' => '44',
            'commercial_id' => null,
        ]);
        $prospectsB = Prospect::factory()->count(2)->create([
            'statut' => 'AC',
            'departement' => '45',
            'commercial_id' => null,
        ]);

        $queue = app(PhoningQueueBuilder::class)->buildDefaultQueue($telepro->id, null);

        $campagneParContact = collect($queue)->map(function ($item) use ($prospectsA) {
            return $prospectsA->contains('id', $item['id']) ? 'A' : 'B';
        })->values()->all();

        $this->assertSame(['A', 'B', 'A', 'B', 'A'], $campagneParContact);
        $this->assertCount(5, $queue);
    }

    #[Test]
    public function a_single_campaign_selection_is_not_affected_by_interleaving(): void
    {
        $telepro = User::factory()->create();

        $campagne = CampagnePhoning::create([
            'nom' => 'Campagne unique',
            'statut' => 'active',
            'type_entite' => 'prospects',
            'criteres' => ['statuts' => ['AC']],
        ]);

        $prospects = Prospect::factory()->count(3)->create([
            'statut' => 'AC',
            'commercial_id' => null,
        ]);

        $queue = app(PhoningQueueBuilder::class)->buildDefaultQueue($telepro->id, $campagne->id);

        $this->assertSame($prospects->pluck('id')->all(), collect($queue)->pluck('id')->all());
    }

    #[Test]
    public function queue_selection_is_balanced_and_not_deterministic_across_campaigns(): void
    {
        $groupe = GroupeTelepro::create(['nom' => 'Groupe aléatoire', 'actif' => true]);
        $telepro = User::factory()->create();
        $telepro->groupesTelepro()->attach($groupe->id);

        foreach (['Campagne A', 'Campagne B', 'Campagne C'] as $nom) {
            CampagnePhoning::create([
                'nom' => $nom,
                'statut' => 'active',
                'type_entite' => 'prospects',
                'groupe_telepro_id' => $groupe->id,
                'criteres' => ['statuts' => ['AC']],
            ]);
        }

        $campagnes = CampagnePhoning::query()->orderBy('id')->get();
        $campagnes->each(function (CampagnePhoning $campagne, int $index) {
            Prospect::factory()->count(2)->create([
                'statut' => 'AC',
                'commercial_id' => null,
                'departement' => (string) (44 + $index),
            ]);
        });

        $queues = [];
        foreach (range(1, 5) as $i) {
            $queues[] = json_encode(app(PhoningQueueBuilder::class)->buildDefaultQueue($telepro->id, null));
        }

        $uniqueQueues = array_unique($queues);

        $this->assertGreaterThan(1, count($uniqueQueues));
        $this->assertTrue(collect($queues)->every(fn ($queue) => str_contains($queue, 'campagne_id')));
    }
}
