<?php

namespace App\Filament\NsConseil\Resources\CrmReportEmailLogResource\Pages;

use App\Filament\NsConseil\Resources\CrmReportEmailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListCrmReportEmailLogs extends ListRecords
{
    protected static string $resource = CrmReportEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

