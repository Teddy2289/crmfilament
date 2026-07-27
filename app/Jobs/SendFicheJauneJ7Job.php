<?php

namespace App\Jobs;

use App\Models\Appel;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\FicheWordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\FicheJauneJ7Mail;

class SendFicheJauneJ7Job implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(FicheWordService $service): void
    {
        // Chercher les appels de J-7 avec statut "CSE-NI" (CSE pas intéressé)
        $dateJMoins7 = now()->subDays(7)->startOfDay();
        $dateJMoins7Fin = now()->subDays(7)->endOfDay();

        $appels = Appel::whereBetween('date_heure', [$dateJMoins7, $dateJMoins7Fin])
            ->where('phoning_status', 'cse_ni')
            ->whereNotNull('fiche_word_path') // S'assurer que la fiche word a déjà été générée
            ->with('appelable')
            ->get();

        if ($appels->isEmpty()) {
            Log::info('Aucun appel trouvé pour l\'envoi de fiches jaunes J+7');
            return;
        }

        Log::info("Traitement de {$appels->count()} appels pour l'envoi de fiches jaunes J+7");

        foreach ($appels as $appel) {
            try {
                // Vérifier si l'email a déjà été envoyé pour éviter les doublons
                if ($appel->fiche_jaune_j7_envoye_at) {
                    continue;
                }

                // Générer l'URL publique de la fiche word si ce n'est pas déjà fait
                if (! $appel->fiche_word_path) {
                    // Générer la fiche jaune si elle n'existe pas encore
                    $template = \App\Models\TemplateFiche::actifs()
                        ->parType('jaune')
                        ->first();

                    if ($template) {
                        $localPath = $service->generer($template, $appel->fiche_data);
                        if ($localPath) {
                            $destination = now()->format('Y/m');
                            $publicUrl = $service->stocker($localPath, $destination);

                            $appel->update([
                                'fiche_word_path' => $publicUrl,
                                'fiche_word_generated_at' => now(),
                            ]);
                        }
                    }
                }

                // Envoyer l'email avec la fiche jaune en pièce jointe
                if ($appel->fiche_word_path) {
                    // Le rappel J+7 est porté par le Responsable de Secteur
                    // assigné au prospect (cf. fiche jaune : "Responsable de
                    // Secteur assigné"), pas par le téléprospecteur qui a
                    // passé l'appel initial. On ne retombe sur ce dernier que
                    // si aucun commercial n'est encore assigné, pour ne pas
                    // perdre le rappel.
                    $prospect = $appel->appelable instanceof Prospect ? $appel->appelable : null;
                    $destinataire = $prospect?->commercial ?: ($appel->user ?: $appel->phoning_agent);

                    if ($destinataire && $destinataire->email) {
                        if (! $prospect?->commercial) {
                            Log::warning("Fiche jaune J+7 : aucun commercial assigné au prospect #{$prospect?->id}, envoi de repli au téléprospecteur #{$destinataire->id}");
                        }

                        Mail::to($destinataire->email)
                            ->queue(new FicheJauneJ7Mail($appel, $destinataire));

                        // Marquer comme envoyé pour éviter les doublons
                        $appel->update([
                            'fiche_jaune_j7_envoye_at' => now(),
                        ]);

                        Log::info("Fiche jaune J+7 envoyée pour l'appel #{$appel->id} à {$destinataire->email}");
                    } else {
                        Log::warning("Fiche jaune J+7 : aucun destinataire disponible pour l'appel #{$appel->id} (ni commercial, ni téléprospecteur)");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'envoi de la fiche jaune J+7 pour l'appel #{$appel->id}: " . $e->getMessage());
                // Continuer avec le prochain appel plutôt que d'arrêter le traitement
                continue;
            }
        }
    }
}