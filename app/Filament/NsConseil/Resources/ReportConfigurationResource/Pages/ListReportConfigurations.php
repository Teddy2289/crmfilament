<?php

declare(strict_types=1);

namespace App\Filament\NsConseil\Resources\ReportConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\ReportConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReportConfigurations extends ListRecords
{
    protected static string $resource = ReportConfigurationResource::class;

    protected static ?string $title = 'Configuration des rapports';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouvelle configuration')
                ->icon('heroicon-o-plus'),
        ];
    }
}
