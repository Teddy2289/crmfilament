<?php

use Illuminate\Support\Facades\Route;

// API routes will be added here
// For now, return a simple response
Route::get('/', function () {
    return response()->json([
        'message' => 'CRM API v1',
        'version' => '1.0.0',
    ]);
});
