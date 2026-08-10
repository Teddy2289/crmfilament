<?php

namespace App\Filament\NsConseil\Pages;

use Filament\Pages\Auth\EditProfile as FilamentEditProfile;

class UserProfile extends FilamentEditProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?string $navigationLabel = 'Mon compte';
    protected static ?int $navigationSort = 0;
    protected static ?string $title = 'Mon compte';
}
