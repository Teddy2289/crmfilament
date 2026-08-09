<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Login as first user
$user = \App\Models\User::first();

$request = Request::create('/ns-conseil/campagne-phonings/3', 'GET');
$request->headers->set('accept', 'text/html');

if ($user) {
    auth()->login($user);
}

try {
    $response = $kernel->handle($request);
    echo "HTTP Status Code: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() === 500) {
        if (isset($response->exception)) {
            echo "EXCEPTION CLASS: " . get_class($response->exception) . "\n";
            echo "MESSAGE: " . $response->exception->getMessage() . "\n";
            echo "FILE: " . $response->exception->getFile() . ":" . $response->exception->getLine() . "\n";
            echo "TRACE:\n" . $response->exception->getTraceAsString() . "\n";
        } else {
            echo "RESPONSE CONTENT (first 2000 chars):\n";
            echo substr($response->getContent(), 0, 2000) . "\n";
        }
    } else {
        echo "Response successful (or non-500). Response status: " . $response->getStatusCode() . " length: " . strlen($response->getContent()) . "\n";
    }
} catch (\Throwable $e) {
    echo "CAUGHT UNHANDLED THROWABLE: " . get_class($e) . "\n";
    echo "MESSAGE: " . $e->getMessage() . "\n";
    echo "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "TRACE:\n" . $e->getTraceAsString() . "\n";
}
