<?php

namespace App\Filament\NsConseil\Resources\OpportuniteResource\Pages;

use App\Filament\NsConseil\Resources\OpportuniteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOpportunite extends EditRecord
{
    protected static string $resource = OpportuniteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
