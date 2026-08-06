<?php
$src = "resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php";
$c = file_get_contents($src);
$lines = explode("\n", $c);
$total = count($lines);
echo "Total lines: $total\n";

// Partial 1: CSS block (lines 9-1879 = idx 8-1878, the <style>...</style>\n<script>...</script> inside @push styles)
$stylesLines = array_slice($lines, 8, 1879-8); // idx 8 to 1877 inclusive
$stylesContent = implode("\n", $stylesLines);
file_put_contents("resources/views/filament/ns-conseil/pages/partials/phoning-workflow-styles.blade.php", $stylesContent);
echo "Styles partial: " . count($stylesLines) . " lines\n";

// Partial 2: Ringover SDK script (lines 1883-3247 = idx 1882-3246, the second @push(scripts))
$sdkLines = array_slice($lines, 1882, 3247-1882); // idx 1882 to 3246 inclusive
$sdkContent = implode("\n", $sdkLines);
file_put_contents("resources/views/filament/ns-conseil/pages/partials/phoning-workflow-scripts.blade.php", $sdkContent);
echo "SDK scripts partial: " . count($sdkLines) . " lines\n";

