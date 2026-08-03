<?php

namespace App\Filament\NsConseil\Resources\EmailConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\EmailConfigurationResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditEmailConfiguration extends EditRecord
{
    protected static string $resource = EmailConfigurationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! Auth::user()?->isSuperAdmin()) {
            $data['is_global'] = false;
            $data['user_id'] = Auth::id();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
