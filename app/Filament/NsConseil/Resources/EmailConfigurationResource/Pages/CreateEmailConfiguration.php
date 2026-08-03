<?php

namespace App\Filament\NsConseil\Resources\EmailConfigurationResource\Pages;

use App\Filament\NsConseil\Resources\EmailConfigurationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEmailConfiguration extends CreateRecord
{
    protected static string $resource = EmailConfigurationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= Auth::id();

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
