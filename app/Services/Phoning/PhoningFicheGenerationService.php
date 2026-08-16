<?php

namespace App\Services\Phoning;

use App\Models\Appel;
use App\Models\Prospect;
use App\Models\RendezVous;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PhoningFicheGenerationService
{
    public function __construct(
        private FichePdfGenerationService $pdfService
    ) {}

    /**
     * Génère les fiches automatiques pour un appel et notifie l'utilisateur.
     */
    public function genererFichesPourAppel(Appel $appel): array
    {
        if (!$appel->fiche_type || !$appel->appelable instanceof Prospect) {
            return [];
        }

        try {
            $prospect = $appel->appelable;
            $rdv = $prospect->rendezVous()->latest('date_heure')->first();

            // Récupérer les données du formulaire depuis fiche_data
            $formData = $appel->fiche_data ?? [];

            $ficheType = $this->determinerTypeFiche($appel->fiche_type);
            $filename = $this->pdfService->genererNomFichier($ficheType, $prospect);

            // Préparer les données selon le type de fiche
            $data = match ($ficheType) {
                'bleue' => $this->pdfService->preparerDonneesFicheBleue($prospect, $rdv, $formData),
                'jaune' => $this->pdfService->preparerDonneesFicheJaune($prospect, $formData),
                'verte' => $this->pdfService->preparerDonneesFicheVerte($prospect, $formData),
                default => throw new \Exception("Type de fiche non supporté : {$ficheType}"),
            };

            // Générer le PDF
            $pdfUrl = $this->pdfService->generer($ficheType, $data, $filename);

            // Mettre à jour l'appel avec le chemin du PDF
            $appel->update([
                'fiche_word_path' => $pdfUrl,
                'fiche_word_generated_at' => now(),
            ]);

            $this->notifierGeneration($filename, $appel);

            Log::info("Fiche PDF générée pour l'appel #{$appel->id}", [
                'type' => $ficheType,
                'fichier' => $filename,
                'url' => $pdfUrl,
            ]);

            return [$pdfUrl];
        } catch (\Exception $e) {
            Log::error("Erreur lors de la génération de fiche PDF pour l'appel #{$appel->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $this->notifierErreur($appel, $e);
            
            return [];
        }
    }

    /**
     * Détermine le type de fiche à partir du fiche_type de l'appel.
     */
    private function determinerTypeFiche(string $ficheType): string
    {
        return match ($ficheType) {
            'bleue' => 'bleue',
            'jaune' => 'jaune',
            'verte' => 'verte',
            default => 'bleue', // Fallback
        };
    }

    private function notifierGeneration(string $filename, Appel $appel): void
    {
        Notification::make()
            ->title('Fiche PDF générée')
            ->body($filename)
            ->success()
            ->send();
    }

    private function notifierErreur(Appel $appel, \Exception $e): void
    {
        Notification::make()
            ->title('Erreur de génération de fiche')
            ->body('La fiche PDF n\'a pas pu être générée. L\'erreur a été loguée.')
            ->danger()
            ->send();
    }
}