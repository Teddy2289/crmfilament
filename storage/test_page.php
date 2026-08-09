<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Livewire\Livewire;
use App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages\ViewCampagnePhoning;

try {
    $user = \App\Models\User::first();
    auth()->login($user);

    echo "Testing Livewire mount for ViewCampagnePhoning record 3...\n";
    $component = Livewire::test(ViewCampagnePhoning::class, [
        'record' => 3,
    ]);

    echo "Mounted successfully! Testing render...\n";
    $html = $component->html();
    echo "Render successful! HTML length: " . strlen($html) . "\n";
} catch (\Throwable $e) {
    echo "Caught exception: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
