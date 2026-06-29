<?php

namespace App\Filament\SuperAdmin\Resources\EntiteCommercialeResource\Pages;

use App\Filament\SuperAdmin\Resources\EntiteCommercialeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEntiteCommerciales extends ListRecords
{
    protected static string $resource = EntiteCommercialeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
