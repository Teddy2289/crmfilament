<?php

declare(strict_types=1);

namespace App\Filament\NsConseil\Resources\ReportConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\ReportConfigurationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateReportConfiguration extends CreateRecord
{
    protected static string $resource = ReportConfigurationResource::class;

    protected static ?string $title = 'Nouvelle configuration de rapport';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['slug'] = $data['slug'] ?: str()->slug($data['name']);

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Configuration de rapport créée';
    }
}
