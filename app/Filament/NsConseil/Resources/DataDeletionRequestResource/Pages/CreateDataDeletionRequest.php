<?php

namespace App\Filament\NsConseil\Resources\DataDeletionRequestResource\Pages;

use App\Filament\NsConseil\Resources\DataDeletionRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateDataDeletionRequest extends CreateRecord
{
    protected static string $resource = DataDeletionRequestResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending';
        return $data;
    }
}
