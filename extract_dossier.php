<?php
$src = "resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php";
$c = file_get_contents($src);
$lines = explode("\n", $c);

// Dossier prospect: idx 2141 to 2537 (lines 2142-2538)
// Note: we keep the wrapper div structure
$dossierLines = array_slice($lines, 2141, 2538-2141);
$dossierContent = implode("\n", $dossierLines);
file_put_contents("resources/views/filament/ns-conseil/pages/partials/phoning-dossier-prospect.blade.php", $dossierContent);
echo "Dossier partial: " . count($dossierLines) . " lines\n";
echo "First: " . rtrim($dossierLines[0]) . "\n";
echo "Last: " . rtrim($dossierLines[count($dossierLines)-1]) . "\n";

// Also check what comes right after the result panel close
for ($i = 2843; $i <= 2870; $i++) {
    echo "L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
}

