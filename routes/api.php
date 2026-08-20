<?php

use App\Http\Controllers\Api\CrmDashboardController;
use App\Http\Controllers\Api\CrmMapController;
use App\Http\Controllers\Api\RingoverDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/ringover/dashboard', [RingoverDashboardController::class, 'getDashboardData'])
        ->name('api.ringover.dashboard');
    
    Route::get('/crm-map', [CrmMapController::class, 'getData'])
        ->name('api.crm.map');
    
    Route::get('/crm-dashboard', [CrmDashboardController::class, 'getData'])
        ->name('api.crm.dashboard');
});
