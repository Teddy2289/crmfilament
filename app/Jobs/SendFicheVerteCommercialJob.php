<?php

namespace App\Jobs;

use App\Mail\FicheVerteCommercialMail;
use App\Models\Appel;
use App\Models\Prospect;
use App\Services\Phoning\FichePdfGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie la fiche verte (BLOC2 / NCSE-50, élu injoignable ou pas de CSE) au
 * Responsable de Secteur assigné au prospect. Contrairement à la fiche jaune
 * (rappel différé à J+7), la fiche verte signale un dossier à reprendre dès
 * que possible : envoi au fil de l'eau, pas de délai fixe.
 */
class SendFicheVerteCommercialJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    private const CODES_FICHE_VERTE = ['bloc2', 'ncse_50'];

    public function handle(FichePdfGenerationService $pdfService): void
    {
        $appels = Appel::whereIn('phoning_status', self::CODES_FICHE_VERTE)
            ->where('fiche_type', 'verte')
            ->whereNull('fiche_verte_envoyee_at')
            ->where('appelable_type', Prospect::class)
            ->with('appelable')
            ->get();

        if ($appels->isEmpty()) {
            return;
        }

        Log::info("Traitement de {$appels->count()} appel(s) pour l'envoi de fiches vertes");

        foreach ($appels as $appel) {
            try {
                if (! $appel->fiche_word_path) {
                    $prospect = $appel->appelable;
                    if ($prospect) {
                        $filename = $pdfService->genererNomFichier('verte', $prospect);
                        $data = $pdfService->preparerDonneesFicheVerte($prospect, $appel->fiche_data ?? []);
                        $pdfUrl = $pdfService->generer('verte', $data, $filename);

                        $appel->update([
                            'fiche_word_path' => $pdfUrl,
                            'fiche_word_generated_at' => now(),
                        ]);
                    }
                }

                if (! $appel->fiche_word_path) {
                    // Pas de PDF généré : on retentera au prochain passage
                    continue;
                }

                /** @var Prospect|null $prospect */
                $prospect = $appel->appelable;
                $destinataire = $prospect?->commercial;

                if (! $destinataire || ! $destinataire->email) {
                    Log::warning("SendFicheVerteCommercialJob : aucun commercial assigné pour l'appel #{$appel->id}, envoi différé");
                    continue;
                }

                Mail::to($destinataire->email)->queue(new FicheVerteCommercialMail($appel));

                $appel->update(['fiche_verte_envoyee_at' => now()]);

                Log::info("Fiche verte envoyée pour l'appel #{$appel->id} à {$destinataire->email}");
            } catch (\Exception $e) {
                Log::error("Erreur lors de l'envoi de la fiche verte pour l'appel #{$appel->id}: " . $e->getMessage());
                continue;
            }
        }
    }
}
