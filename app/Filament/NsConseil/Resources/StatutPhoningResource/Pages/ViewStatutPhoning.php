<?php

namespace App\Filament\NsConseil\Resources\StatutPhoningResource\Pages;

use App\Filament\NsConseil\Resources\StatutPhoningResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStatutPhoning extends ViewRecord
{
    protected static string $resource = StatutPhoningResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
