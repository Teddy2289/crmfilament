<?php

declare(strict_types=1);

namespace App\Filament\NsConseil\Resources\ReportConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\ReportConfigurationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReportConfiguration extends EditRecord
{
    protected static string $resource = ReportConfigurationResource::class;

    protected static ?string $title = 'Modifier la configuration';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Voir'),
            Actions\DeleteAction::make()->label('Supprimer'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Configuration de rapport mise à jour';
    }
}
