<?php

namespace App\Jobs;

use App\Filament\NsConseil\Resources\PartenaireResource;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\Task;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendRappelRdvJob implements ShouldQueue
{
    use Queueable;

    /**
     * Crée une tâche de rappel pour le téléprospecteur (ou le commercial, à défaut)
     * à l'heure du rendez-vous convenue avec le prospect.
     */
    public function handle(): void
    {
        $rdvs = RendezVous::rappelAEnvoyer()
            ->with(['rdvable', 'teleprospecteur', 'commercial'])
            ->get();

        foreach ($rdvs as $rdv) {
            $assigneA = $rdv->teleprospecteur_id ?? $rdv->commercial_id;

            if (! $assigneA) {
                continue;
            }

            $nomContact = $rdv->interlocuteur_nom ?: $this->nomEntite($rdv->rdvable);

            Task::create([
                'titre' => "Rappel RDV : {$nomContact}",
                'description' => "Rendez-vous prévu à l'heure convenue avec le prospect.\n".
                    "Date/heure : {$rdv->date_heure->format('d/m/Y H:i')}\n".
                    ($rdv->interlocuteur_tel ? "Téléphone : {$rdv->interlocuteur_tel}\n" : '').
                    ($rdv->lieu_complet ? "Lieu : {$rdv->lieu_complet}" : ''),
                'type' => 'rappel',
                'statut' => 'a_faire',
                'date_echeance' => $rdv->date_heure,
                'assigne_a' => $assigneA,
                'prospect_id' => $rdv->rdvable instanceof Prospect ? $rdv->rdvable_id : null,
                'partenaire_id' => $rdv->rdvable instanceof Partenaire ? $rdv->rdvable_id : null,
            ]);

            $rdv->update(['rappel_envoye_at' => now()]);

            $this->notifierUtilisateur($assigneA, $nomContact, $rdv);

            Log::info("Rappel RDV créé pour rendez-vous #{$rdv->id} — assigné à l'utilisateur #{$assigneA}");
        }

        Log::info("Rappel RDV : {$rdvs->count()} rappel(s) créé(s)");
    }

    private function nomEntite(?\Illuminate\Database\Eloquent\Model $entite): string
    {
        if (! $entite) {
            return 'Contact';
        }

        return $entite->nom ?? $entite->raison_sociale ?? 'Contact #'.$entite->getKey();
    }

    private function notifierUtilisateur(int $userId, string $nomContact, RendezVous $rdv): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $url = match (true) {
            $rdv->rdvable instanceof Prospect => ProspectResource::getUrl('view', ['record' => $rdv->rdvable_id], panel: 'ns-conseil'),
            $rdv->rdvable instanceof Partenaire => PartenaireResource::getUrl('view', ['record' => $rdv->rdvable_id], panel: 'ns-conseil'),
            default => null,
        };

        Notification::make()
            ->title("Rappel RDV : {$nomContact}")
            ->body("Rendez-vous prévu maintenant — {$rdv->date_heure->format('d/m/Y H:i')}")
            ->icon('heroicon-o-bell-alert')
            ->warning()
            ->when($url, fn (Notification $n) => $n->actions([
                \Filament\Notifications\Actions\Action::make('voir')
                    ->label('Voir la fiche')
                    ->url($url),
            ]))
            ->sendToDatabase($user);
    }
}
