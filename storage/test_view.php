<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    echo "Finding CampagnePhoning ID 3...\n";
    $campagne = \App\Models\CampagnePhoning::find(3);
    if (!$campagne) {
        echo "CampagnePhoning ID 3 not found!\n";
        exit(1);
    }
    echo "Found campaign: " . $campagne->nom . "\n";
    echo "Statut: " . $campagne->statut . "\n";
    echo "Type entite: " . $campagne->type_entite . "\n";

    echo "Testing getStats()...\n";
    $stats = $campagne->getStats();
    print_r($stats);

    echo "Testing countQueueContacts()...\n";
    echo $campagne->countQueueContacts() . "\n";

    echo "Testing buildQueueQuery()->get()...\n";
    $queue = $campagne->buildQueueQuery()->get();
    echo "Queue count: " . $queue->count() . "\n";

    echo "Testing statutsUtilises()...\n";
    $statuts = $campagne->statutsUtilises();
    print_r($statuts);

    foreach ($statuts as $st) {
        echo "Testing appelsParStatut('$st')...\n";
        $appels = $campagne->appelsParStatut($st);
        echo "Count: " . $appels->count() . "\n";
    }

    echo "ALL MODEL CHECKS PASSED!\n";
} catch (\Throwable $e) {
    echo "Caught exception: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
