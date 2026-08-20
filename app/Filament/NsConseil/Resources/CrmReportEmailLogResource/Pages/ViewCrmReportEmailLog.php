<?php

namespace App\Filament\NsConseil\Resources\CrmReportEmailLogResource\Pages;

use App\Filament\NsConseil\Resources\CrmReportEmailLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCrmReportEmailLog extends ViewRecord
{
    protected static string $resource = CrmReportEmailLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}

