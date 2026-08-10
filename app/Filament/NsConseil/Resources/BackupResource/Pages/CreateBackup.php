<?php

namespace App\Filament\NsConseil\Resources\BackupResource\Pages;

use App\Filament\NsConseil\Resources\BackupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBackup extends CreateRecord
{
    protected static string $resource = BackupResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['status'] = 'pending';
        return $data;
    }
}
