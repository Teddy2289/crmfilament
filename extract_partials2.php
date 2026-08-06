<?php
$src = "resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php";
$c = file_get_contents($src);
$lines = explode("\n", $c);
$total = count($lines);

// Find exact boundaries via pattern matching
// Search results: starts after the search bar ends and @if ($showSearchResults, ends at @endif before @if ($currentContact)
// Contact card: starts at line 2033 comment "CARD ENTREPRISE" ends around 2137

// Partial 3: Search results (lines 1985-2029 = idx 1984-2028)
$searchLines = array_slice($lines, 1984, 2029-1984);
$searchContent = implode("\n", $searchLines);
file_put_contents("resources/views/filament/ns-conseil/pages/partials/phoning-search-results.blade.php", $searchContent);
echo "Search results partial: " . count($searchLines) . " lines\n";
echo "First: " . rtrim($searchLines[0]) . "\n";
echo "Last: " . rtrim($searchLines[count($searchLines)-1]) . "\n";

// Let us find what lines contain the dossier prospect section
for ($i = 2139; $i <= 2145; $i++) {
    echo "L".($i+1).": " . rtrim($lines[$i]) . "\n";
}
echo "...\n";
for ($i = 2630; $i <= 2636; $i++) {
    echo "L".($i+1).": " . rtrim($lines[$i]) . "\n";
}

