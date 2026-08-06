<?php

namespace App\Filament\NsConseil\Resources\RendezVousAssociationResource\Pages;

use App\Filament\NsConseil\Resources\RendezVousAssociationResource;
use Filament\Resources\Pages\ViewRecord;

class ViewRendezVousAssociation extends ViewRecord
{
    protected static string $resource = RendezVousAssociationResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->check() && auth()->user()?->actif;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return static::canAccess();
    }
}

