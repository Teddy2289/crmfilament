<?php

namespace App\Filament\SuperAdmin\Resources\UserResource\Pages;

use App\Filament\SuperAdmin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;

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
            'tous'     => \Filament\Resources\Components\Tab::make('Tous')
                ->badge(\App\Models\User::count()),
            'actifs'   => \Filament\Resources\Components\Tab::make('Actifs')
                ->badge(\App\Models\User::where('actif', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($q) => $q->where('actif', true)),
            'inactifs' => \Filament\Resources\Components\Tab::make('Désactivés')
                ->badge(\App\Models\User::where('actif', false)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->where('actif', false)),
        ];
    }
}
