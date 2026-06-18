<?php

namespace App\Listeners;

use App\Events\Mail2EnvoyeEvent;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class NotifierTeamLeaderMail2Listener
{
    public function handle(Mail2EnvoyeEvent $event): void
    {
        $prospect = $event->prospect;

        $teamLeaders = User::whereHas(
            'roles',
            fn ($q) => $q->whereIn('name', ['team_leader', 'admin', 'super_admin'])
        )->get();

        if ($teamLeaders->isEmpty()) {
            return;
        }

        Notification::make()
            ->title('Mail 2 envoyé — vérification QF disponible')
            ->body('Prospect : ' . ($prospect->raison_sociale ?? $prospect->nom))
            ->actions([
                Action::make('voir')
                    ->label('Ouvrir la fiche')
                    ->url(ProspectResource::getUrl('view', ['record' => $prospect]))
                    ->button(),
            ])
            ->sendToDatabase($teamLeaders);
    }
}
