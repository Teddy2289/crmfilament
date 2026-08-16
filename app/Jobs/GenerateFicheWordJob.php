<?php

namespace App\Jobs;

use App\Models\Appel;
use App\Services\Phoning\FichePdfGenerationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateFicheWordJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $appelId
    ) {
    }

    public function handle(FichePdfGenerationService $pdfService): void
    {
        $appel = Appel::find($this->appelId);

        if (! $appel || ! $appel->fiche_type) {
            Log::warning("Appel #{$this->appelId} introuvable ou sans type de fiche");
            return;
        }

        try {
            $prospect = $appel->appelable;
            if (! $prospect) {
                Log::warning("Appel #{$this->appelId} : prospect introuvable");
                return;
            }

            $ficheType = match ($appel->fiche_type) {
                'bleue' => 'bleue',
                'jaune' => 'jaune',
                'verte' => 'verte',
                default => 'bleue',
            };

            $filename = $pdfService->genererNomFichier($ficheType, $prospect);
            $formData = $appel->fiche_data ?? [];
            
            $data = match ($ficheType) {
                'bleue' => $pdfService->preparerDonneesFicheBleue($prospect, null, $formData),
                'jaune' => $pdfService->preparerDonneesFicheJaune($prospect, $formData),
                'verte' => $pdfService->preparerDonneesFicheVerte($prospect, $formData),
                default => [],
            };

            $pdfUrl = $pdfService->generer($ficheType, $data, $filename);

            if ($pdfUrl) {
                $appel->update([
                    'fiche_word_path' => $pdfUrl,
                    'fiche_word_generated_at' => now(),
                ]);

                Log::info("Fiche PDF générée pour appel #{$this->appelId} : {$pdfUrl}");
            }
        } catch (\Exception $e) {
            Log::error("Erreur génération fiche PDF pour appel #{$this->appelId} : ".$e->getMessage());
            throw $e;
        }
    }
}
