<?php

namespace App\Filament\NsConseil\Resources\AuditLogResource\Pages;

use App\Filament\NsConseil\Resources\AuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
