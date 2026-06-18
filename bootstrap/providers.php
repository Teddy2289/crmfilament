<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AlloproPanelProvider;
use App\Providers\Filament\NsConseilPanelProvider;
use App\Providers\Filament\SuperAdminPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    AlloproPanelProvider::class,
    NsConseilPanelProvider::class,
    SuperAdminPanelProvider::class,
];
