<?php

namespace App\Filament\Allopro\Resources\TicketResource\Pages;

use App\Filament\Allopro\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return TicketResource::filterFormDataForFieldPermissions($data, 'edit');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasRole('responsable_plateau')),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Ticket mis à jour';
    }
}
