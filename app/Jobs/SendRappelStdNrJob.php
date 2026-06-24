<?php

namespace App\Jobs;

use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Models\Task;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendRappelStdNrJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     * Rappel automatique J+2 pour prospects avec statut STD-NR (Standard Non Répondu)
     */
    public function handle(): void
    {
        // Récupérer le statut STD-NR
        $statutStdNr = StatutPhoning::where('code', 'STD-NR')->first();
        
        if (! $statutStdNr) {
            Log::warning('Rappel STD-NR: statut STD-NR non trouvé');
            return;
        }

        // Récupérer les prospects avec statut STD-NR depuis exactement 2 jours
        $prospects = Prospect::where('statut_phoning_code', 'STD-NR')
            ->whereDate('updated_at', now()->subDays(2))
            ->whereNull('rappel_std_nr_envoye_at')
            ->with('teleprospecteur')
            ->get();

        foreach ($prospects as $prospect) {
            if (! $prospect->teleprospecteur) {
                continue;
            }

            // Créer une tâche de rappel pour le téléprospecteur
            Task::create([
                'titre' => "Rappel STD-NR : {$prospect->nom}",
                'description' => "Rappel automatique J+2 pour prospect avec statut STD-NR (Standard Non Répondu)\n".
                    "Prospect : {$prospect->nom} ({$prospect->raison_sociale})\n".
                    "Téléphone : {$prospect->telephone}\n".
                    "Statut actuel : STD-NR depuis le {$prospect->updated_at->format('d/m/Y')}",
                'type' => 'rappel',
                'statut' => 'a_faire',
                'date_echeance' => now()->addHours(2), // Rappel dans 2h
                'assigne_a' => $prospect->teleprospecteur_id,
                'prospect_id' => $prospect->id,
                'created_by' => $prospect->teleprospecteur_id,
            ]);

            // Marquer le rappel comme envoyé
            $prospect->update(['rappel_std_nr_envoye_at' => now()]);

            Log::info("Rappel STD-NR créé pour prospect #{$prospect->id} - Téléprospecteur : {$prospect->teleprospecteur->name}");
        }

        Log::info("Rappel STD-NR : {$prospects->count()} rappels créés");
    }
}
