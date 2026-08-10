<?php

namespace App\Filament\NsConseil\Resources\AuditLogResource\Pages;

use App\Filament\NsConseil\Resources\AuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Retour')
                ->url(AuditLogResource::getUrl('index')),
        ];
    }
}
