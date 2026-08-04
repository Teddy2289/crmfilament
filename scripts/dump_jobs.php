<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('jobs')->orderBy('id','desc')->limit(200)->get();
foreach ($rows as $r) {
    $p = json_decode($r->payload, true);
    $jobClass = null;
    if (isset($p['data']['command'])) {
        $jobClass = $p['data']['command'];
    }
    if (isset($p['data']['job'])) {
        $jobClass = $p['data']['job'];
    }
    echo "ID: {$r->id}  Queue: {$r->queue}  Attempts: {$r->attempts}\n";
    echo "Available_at: {$r->available_at}  Created_at: {$r->created_at}\n";
    echo "JobClass: " . json_encode($jobClass) . "\n";
    echo "---- payload snippet ----\n";
    echo substr($r->payload, 0, 2000) . "\n";
    echo "-------------------------\n\n";
}
