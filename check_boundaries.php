<?php
$src = "resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php";
$c = file_get_contents($src);
$lines = explode("\n", $c);

// Show lines 2031-2145 to understand the structure between @if($currentContact) and dossier
echo "=== Lines 2031-2145 ===\n";
for ($i = 2030; $i <= 2144; $i++) {
    echo "L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
}

// Show lines 2895-2910 to understand email preview start
echo "=== Lines 2895-2910 ===\n";
for ($i = 2894; $i <= 2910; $i++) {
    echo "L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
}

// Show lines 3025-3040 to understand email preview end
echo "=== Lines 3025-3040 ===\n";
for ($i = 3024; $i <= 3040; $i++) {
    echo "L" . ($i+1) . ": " . rtrim($lines[$i]) . "\n";
}

