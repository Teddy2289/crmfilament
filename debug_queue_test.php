<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CampagnePhoning;
use App\Models\Prospect;
use App\Models\GroupeTelepro;
use App\Models\User;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();

try {
    $groupe = GroupeTelepro::create(['nom' => 'Test Groupe', 'actif' => true]);
    $telepro = User::factory()->create();
    $telepro->groupesTelepro()->attach($groupe->id);

    $active = CampagnePhoning::create([
        'nom' => 'Active',
        'statut' => 'active',
        'type_entite' => 'prospects',
        'groupe_telepro_id' => $groupe->id,
        'criteres' => ['statuts' => ['AC']],
    ]);

    $paused = CampagnePhoning::create([
        'nom' => 'Paused',
        'statut' => 'en_pause',
        'type_entite' => 'prospects',
        'groupe_telepro_id' => $groupe->id,
        'criteres' => ['statuts' => ['AC']],
    ]);

    $p1 = Prospect::factory()->create(['statut' => 'AC', 'commercial_id' => null]);
    $p2 = Prospect::factory()->create(['statut' => 'AC', 'commercial_id' => null]);

    echo 'p1 id: ' . $p1->id . ', teleprospecteur_id: ' . $p1->teleprospecteur_id . PHP_EOL;
    echo 'p2 id: ' . $p2->id . ', teleprospecteur_id: ' . $p2->teleprospecteur_id . PHP_EOL;
    echo 'p1 telephone: ' . $p1->telephone . PHP_EOL;
    echo 'p1 statut: ' . $p1->statut->value . PHP_EOL;

    // Check what the active campaign query returns
    $activeQueue = $active->getContactsQueue();
    echo 'Active campaign queue: ' . json_encode($activeQueue) . PHP_EOL;

    $queue = app(\App\Services\Phoning\PhoningQueueBuilder::class)->buildDefaultQueue($telepro->id, null);
    echo 'Full queue count: ' . count($queue) . PHP_EOL;
    foreach ($queue as $item) {
        echo 'Queue item: ' . json_encode($item) . PHP_EOL;
    }
} finally {
    DB::rollBack();
}
