<?php

use App\Http\Controllers\Api\RingoverDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/ringover/dashboard', [RingoverDashboardController::class, 'getDashboardData'])
        ->name('api.ringover.dashboard');
});
