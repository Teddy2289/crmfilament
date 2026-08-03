<?php

namespace App\Filament\NsConseil\Resources\AppelResource\Pages;

use App\Filament\NsConseil\Resources\AppelResource;
use App\Models\Appel;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListAppels extends ListRecords
{
    protected static string $resource = AppelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Pas de création manuelle d'appels
        ];
    }

    protected function getTableQuery(): Builder
    {
        return Appel::query()
            ->with([
                'user:id,nom,prenom,email',
                'appelable:id,nom,telephone',
            ])
            ->where('user_id', auth()->id());
    }
}
