<?php

namespace App\Filament\Allopro\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Tableau de bord';

    protected static string $view = 'filament.allopro.pages.dashboard';

    protected static ?int $navigationSort = -2;
}
