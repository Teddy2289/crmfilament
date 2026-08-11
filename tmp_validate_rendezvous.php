<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RendezVous;
use Illuminate\Support\Facades\DB;

try {
    $models = RendezVous::all();
    foreach ($models as $model) {
        echo $model->id . ' -> ' . $model->statut->value . "\n";
    }
    $default = DB::selectOne("SELECT COLUMN_DEFAULT, HEX(COLUMN_DEFAULT) AS hex_default FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'rendez_vous' AND column_name='statut'");
    echo 'DEFAULT=' . $default->COLUMN_DEFAULT . ' HEX=' . $default->hex_default . "\n";
} catch (Throwable $e) {
    echo 'ERROR: ' . get_class($e) . ' - ' . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
