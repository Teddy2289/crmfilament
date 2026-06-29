<?php

namespace App\Filament\SuperAdmin\Resources\FieldPermissionResource\Pages;

use App\Filament\SuperAdmin\Resources\FieldPermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFieldPermissions extends ListRecords
{
    protected static string $resource = FieldPermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
