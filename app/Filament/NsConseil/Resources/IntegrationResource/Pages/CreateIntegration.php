<?php

namespace App\Filament\NsConseil\Resources\IntegrationResource\Pages;

use App\Filament\NsConseil\Resources\IntegrationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateIntegration extends CreateRecord
{
    protected static string $resource = IntegrationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['verified'] = false;
        return $data;
    }
}
