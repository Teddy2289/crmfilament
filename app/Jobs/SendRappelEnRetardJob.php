<?php

namespace App\Jobs;

use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\Prospect;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Notifie (cloche Filament) le téléprospecteur ou commercial assigné dès
 * qu'un rappel programmé sur un prospect passe en retard. Chaque prospect
 * n'est notifié qu'une fois par rappel en retard grâce à
 * rappel_notifie_retard_at, remis à zéro à chaque nouvelle planification
 * (Prospect::programmerRappel/annulerRappel).
 */
class SendRappelEnRetardJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $prospects = Prospect::rappelEnRetard()
            ->whereNull('rappel_notifie_retard_at')
            ->get();

        foreach ($prospects as $prospect) {
            $assigneA = $prospect->teleprospecteur_id ?? $prospect->commercial_id;

            if (! $assigneA) {
                continue;
            }

            $this->notifier($assigneA, $prospect);
            $prospect->update(['rappel_notifie_retard_at' => now()]);
        }

        Log::info("Rappel en retard : {$prospects->count()} notification(s) envoyée(s)");
    }

    private function notifier(int $userId, Prospect $prospect): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $body = sprintf(
            '<div><div style="margin-bottom:2px;"><strong>Rappel prévu le :</strong> %s</div><div><strong>Retard :</strong> %s</div></div>',
            e($prospect->rappel_planifie_at->format('d/m/Y à H:i')),
            e($prospect->rappel_planifie_at->diffForHumans()),
        );

        Notification::make()
            ->title("Rappel en retard : {$prospect->nom}")
            ->body($body)
            ->icon('heroicon-o-phone-x-mark')
            ->warning()
            ->actions([
                Action::make('voir')
                    ->label('Voir la fiche')
                    ->url(ProspectResource::getUrl('view', ['record' => $prospect->id], panel: 'ns-conseil')),
            ])
            ->sendToDatabase($user);
    }
}
