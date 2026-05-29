<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Filament\NsConseil\Resources\ProspectResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewProspect extends ViewRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
