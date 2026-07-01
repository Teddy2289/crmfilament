<?php

namespace App\Filament\SuperAdmin\Resources\RingoverApiKeyResource\Pages;

use App\Filament\SuperAdmin\Resources\RingoverApiKeyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRingoverApiKeys extends ListRecords
{
    protected static string $resource = RingoverApiKeyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
