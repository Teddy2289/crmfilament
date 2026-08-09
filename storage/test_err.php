<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

$request = Illuminate\Http\Request::create('/ns-conseil/campagne-phonings/3', 'GET');
$app->instance('request', $request);

$user = \App\Models\User::first();
if ($user) {
    auth()->guard('web')->setUser($user);
}

try {
    $response = $kernel->handle($request);
    echo "Status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 500) {
        if (isset($response->exception)) {
            echo "Exception: " . get_class($response->exception) . ": " . $response->exception->getMessage() . "\n";
            echo $response->exception->getFile() . ":" . $response->exception->getLine() . "\n";
            echo $response->exception->getTraceAsString() . "\n";
        } else {
            echo substr($response->getContent(), 0, 3000) . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "Caught Throwable: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}
