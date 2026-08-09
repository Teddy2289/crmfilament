<?php

namespace App\Filament\NsConseil\Resources\UserDashboardResource\Pages;

use App\Filament\NsConseil\Resources\UserDashboardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserDashboard extends EditRecord
{
    protected static string $resource = UserDashboardResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
