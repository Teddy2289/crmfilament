<?php

namespace App\Filament\NsConseil\Resources\GroupeTeleproResource\Pages;

use App\Filament\NsConseil\Resources\GroupeTeleproResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGroupeTelepros extends ListRecords
{
    protected static string $resource = GroupeTeleproResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
