<?php
$f = "resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php";
$c = file_get_contents($f);
$lines = explode("\n", $c);
echo "Total: " . count($lines) . "\n";
foreach ([8,9,1878,1879,1881,1882,1913,1914,3032,3246,3247,3251] as $i) {
    echo "L".($i+1).": " . rtrim($lines[$i]) . "\n";
}

