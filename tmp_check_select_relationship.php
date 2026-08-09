<?php
require __DIR__ . '/vendor/autoload.php';

use Filament\Tables\Filters\SelectFilter;

$filter = SelectFilter::make('commercial_id')->relationship('commercial', 'nom');
var_dump($filter->getRelationshipName());
try {
    $relationship = $filter->getRelationship();
    var_dump($relationship);
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n";
}
