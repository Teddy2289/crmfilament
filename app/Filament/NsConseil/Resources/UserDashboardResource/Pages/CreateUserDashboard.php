<?php

namespace App\Filament\NsConseil\Resources\UserDashboardResource\Pages;

use App\Filament\NsConseil\Resources\UserDashboardResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUserDashboard extends CreateRecord
{
    protected static string $resource = UserDashboardResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        
        // Si c'est le premier dashboard, le mettre par défaut
        $userDashboardsCount = \App\Models\UserDashboard::where('user_id', auth()->id())->count();
        if ($userDashboardsCount === 0) {
            $data['par_defaut'] = true;
        }
        
        return $data;
    }
}
