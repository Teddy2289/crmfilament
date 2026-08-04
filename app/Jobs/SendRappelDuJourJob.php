<?php

namespace App\Jobs;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\Prospect;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Queueable;
use Illuminate\Support\Facades\Log;

class SendRappelDuJourJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $prospects = Prospect::query()
            ->whereDate('rappel_planifie_at', now()->toDateString())
            ->whereNotNull('teleprospecteur_id')
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->with('teleprospecteur')
            ->orderBy('rappel_planifie_at')
            ->get();

        foreach ($prospects as $prospect) {
            if (! $prospect->teleprospecteur) {
                continue;
            }

            $hasTodayRappelTask = \App\Models\Task::query()
                ->where('prospect_id', $prospect->id)
                ->where('type', 'rappel')
                ->where('assigne_a', $prospect->teleprospecteur_id)
                ->whereDate('date_echeance', today())
                ->whereNotIn('statut', ['terminee', 'annulee'])
                ->exists();

            if ($hasTodayRappelTask) {
                continue;
            }

            $this->notifier($prospect->teleprospecteur, $prospect);
        }

        Log::info("Rappel du jour : {$prospects->count()} notification(s) envoyée(s)");
    }

    private function notifier(User $user, Prospect $prospect): void
    {
        $url = ProspectResource::getUrl('view', ['record' => $prospect->id], panel: 'ns-conseil');

        $body = sprintf(
            '<div><div style="margin-bottom:0.5rem;"><strong>Prospect :</strong> %s</div><div><strong>Rappel prévu :</strong> %s</div></div>',
            e($prospect->nom),
            e($prospect->rappel_planifie_at->format('d/m/Y H:i'))
        );

        Notification::make()
            ->title("Rappel aujourd'hui : {$prospect->nom}")
            ->body($body)
            ->icon('heroicon-o-bell-alert')
            ->warning()
            ->actions([
                \Filament\Notifications\Actions\Action::make('voir')
                    ->label('Voir la fiche')
                    ->url($url),
            ])
            ->sendToDatabase($user);
    }
}
