<?php

namespace App\Jobs;

use App\Models\Prospect;
use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendRappelRpJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * WF7 — Rappel RP : Créer une tâche de rappel pour le téléprospecteur
     * quand un prospect a le statut RP avec une date/heure planifiée.
     */
    public function handle(): void
    {
        // Récupérer les prospects avec statut RP et une date de rappel planifiée
        $prospects = Prospect::where('statut_phoning', 'rp')
            ->whereNotNull('rappel_date')
            ->where('rappel_date', '<=', now()->addHours(1)) // Rappels dans l'heure qui suit
            ->whereNull('rappel_envoye_at')
            ->with('teleprospecteur')
            ->get();

        foreach ($prospects as $prospect) {
            if (! $prospect->teleprospecteur) {
                continue;
            }

            // Créer une tâche de rappel pour le téléprospecteur
            Task::create([
                'titre' => "Rappel CSE : {$prospect->nom}",
                'description' => "Rappel planifié pour le prospect {$prospect->nom} ({$prospect->raison_sociale})\n".
                    "Date de rappel : {$prospect->rappel_date->format('d/m/Y H:i')}\n".
                    "Téléphone : {$prospect->telephone}",
                'type' => 'rappel',
                'statut' => 'a_faire',
                'date_echeance' => $prospect->rappel_date,
                'assigne_a' => $prospect->teleprospecteur_id,
                'prospect_id' => $prospect->id,
            ]);

            // Marquer le rappel comme envoyé
            $prospect->update(['rappel_envoye_at' => now()]);

            Log::info("Rappel RP créé pour prospect #{$prospect->id} - Téléprospecteur : {$prospect->teleprospecteur->name}");
        }

        Log::info("WF7 Rappel RP : {$prospects->count()} rappels créés");
    }
}
