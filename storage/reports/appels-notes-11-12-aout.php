<?php

/**
 * Script de génération du rapport des appels et notes du 11-12 août 2026
 * Ce script génère un fichier texte avec toutes les notes associées aux appels Ringover
 */

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Appel;
use Carbon\Carbon;

$appels = Appel::whereBetween('date_heure', [
    Carbon::parse('2026-08-11 00:00:00'),
    Carbon::parse('2026-08-12 23:59:59')
])
->with('prospect')
->orderBy('date_heure', 'desc')
->get();

$report = "RAPPORT DES APPELS RINGOVER - 11 ET 12 AOÛT 2026\n";
$report .= "=".str_repeat("=", 70)."\n";
$report .= "Date généré: " . date('d/m/Y H:i:s') . "\n";
$report .= "Total appels: " . $appels->count() . "\n\n";

$appelsAvecNotes = 0;
$appelsSansNotes = 0;
$notes = [];

foreach ($appels as $appel) {
    $prospectNotes = $appel->prospect?->description ?: '';
    $appelsNotes = $appel->notes ?: '';
    
    if (!empty($prospectNotes) || !empty($appelsNotes)) {
        $appelsAvecNotes++;
        $notes[] = [
            'id' => $appel->id,
            'heure' => $appel->date_heure,
            'prospect' => $appel->prospect?->nom,
            'prospect_notes' => $prospectNotes,
            'appel_notes' => $appelsNotes,
            'resultat' => $appel->phoning_result ?: '—',
            'duree' => $appel->duree_formatee,
            'ringover_id' => $appel->ringover_call_id,
        ];
    } else {
        $appelsSansNotes++;
    }
}

foreach ($notes as $note) {
    $report .= str_repeat("-", 80) . "\n";
    $report .= "ID APPEL: " . $note['id'] . " | Ringover ID: " . $note['ringover_id'] . "\n";
    $report .= "HEURE: " . $note['heure']->format('d/m/Y H:i:s') . "\n";
    $report .= "PROSPECT: " . $note['prospect'] . "\n";
    $report .= "RÉSULTAT: " . $note['resultat'] . "\n";
    $report .= "DURÉE: " . ($note['duree'] ?? '—') . "\n\n";
    
    if (!empty($note['prospect_notes'])) {
        $report .= "📝 NOTES PROSPECT:\n";
        $report .= $note['prospect_notes'] . "\n\n";
    }
    
    if (!empty($note['appel_notes'])) {
        $report .= "📝 NOTES APPEL:\n";
        $report .= $note['appel_notes'] . "\n\n";
    }
}

$report .= "\n" . str_repeat("=", 80) . "\n";
$report .= "RÉSUMÉ\n";
$report .= "=".str_repeat("=", 70)."\n";
$report .= "✓ Appels avec notes: " . $appelsAvecNotes . "\n";
$report .= "✗ Appels sans notes: " . $appelsSansNotes . "\n";
$report .= "TOTAL: " . $appels->count() . "\n";

// Save to file
$filename = storage_path('reports/notes_appels_11_12_aout_' . date('YmdHis') . '.txt');
file_put_contents($filename, $report);

echo $report;
echo "\n✓ Rapport sauvegardé: " . $filename . "\n";
