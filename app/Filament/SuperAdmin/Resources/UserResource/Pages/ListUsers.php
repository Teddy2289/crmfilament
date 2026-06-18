<?php

namespace App\Filament\SuperAdmin\Resources\UserResource\Pages;

use App\Filament\SuperAdmin\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nouvel utilisateur')->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'tous' => Tab::make('Tous')
                ->badge(User::count()),
            'actifs' => Tab::make('Actifs')
                ->badge(User::where('actif', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn ($q) => $q->where('actif', true)),
            'inactifs' => Tab::make('Désactivés')
                ->badge(User::where('actif', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn ($q) => $q->where('actif', false)),
        ];
    }
}
