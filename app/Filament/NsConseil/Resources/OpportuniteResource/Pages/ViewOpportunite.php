<?php

namespace App\Filament\NsConseil\Resources\OpportuniteResource\Pages;

use App\Filament\NsConseil\Resources\OpportuniteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunite extends ViewRecord
{
    protected static string $resource = OpportuniteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
