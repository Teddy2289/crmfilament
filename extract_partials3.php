<?php
$src = "resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php";
$c = file_get_contents($src);
$lines = explode("\n", $c);

// Find end of dossier prospect (the </div> that closes pw-infos pw-card)
// It starts at idx 2141 (line 2142). 
// Looking for where the result panel starts

// Find where "RÉSULTAT DE L APPEL" comment is
$resultPanelStart = null;
for ($i = 2540; $i <= 2560; $i++) {
    if (strpos($lines[$i], "RÉSULTAT DE L") !== false || strpos($lines[$i], "R\xc3\x89SULTAT") !== false) {
        $resultPanelStart = $i;
        echo "Found result panel at L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
        break;
    }
}

// check lines around 2540
for ($i = 2537; $i <= 2545; $i++) {
    echo "L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
}
echo "---\n";
// Find the closing tag before pw-right
for ($i = 2840; $i <= 2855; $i++) {
    echo "L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
}

