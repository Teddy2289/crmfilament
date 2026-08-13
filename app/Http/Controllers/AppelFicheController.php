<?php

namespace App\Http\Controllers;

use App\Models\Appel;
use App\Services\Crm\FicheWordService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class AppelFicheController extends Controller
{
    /**
     * Télécharge une fiche Word en la régénérant d'abord.
     * 
     * Processus:
     * 1. Récupère l'appel (avec vérification d'accès)
     * 2. Régénère la fiche Word à partir des données actuelles
     * 3. Stocke le fichier généré
     * 4. Retourne le fichier au client
     */
    public function __invoke(Appel $appel, FicheWordService $ficheService): Response
    {
        // Vérifier que l'utilisateur peut télécharger cette fiche
        $this->authorizeDownload($appel);

        // Charger le type de fiche si nécessaire
        if (! $appel->fiche_type || ! $appel->fiche_data) {
            abort(404, 'Aucune fiche à générer pour cet appel');
        }

        try {
            // Régénérer la fiche
            $localPath = $ficheService->genererPourAppel($appel);

            if (! $localPath) {
                abort(500, 'Impossible de générer la fiche');
            }

            // Stocker le fichier (et obtenir l'URL publique)
            $destination = now()->format('Y/m');
            $publicUrl = $ficheService->stocker($localPath, $destination);

            // Mettre à jour l'appel avec le nouveau chemin et la date
            $appel->update([
                'fiche_word_path' => $publicUrl,
                'fiche_word_generated_at' => now(),
            ]);

            // Extraire le chemin relatif du disque public
            // L'URL ressemble à : /storage/fiches/2026/08/fiche-verte-20260812-123456.docx
            $relativePath = str_replace(asset('storage') . '/', '', $publicUrl);
            
            // Récupérer le fichier depuis le disque public
            if (! Storage::disk('public')->exists($relativePath)) {
                abort(404, 'Le fichier généré est introuvable');
            }

            // Retourner le fichier au navigateur
            return response()->file(
                Storage::disk('public')->path($relativePath),
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'Content-Disposition' => 'attachment; filename="' . basename($relativePath) . '"',
                ]
            );
        } catch (\Exception $e) {
            \Log::error("Erreur téléchargement fiche Word appel #{$appel->id}: " . $e->getMessage());
            abort(500, 'Erreur lors de la génération de la fiche');
        }
    }

    /**
     * Vérifier que l'utilisateur peut télécharger cette fiche.
     */
    protected function authorizeDownload(Appel $appel): void
    {
        $user = Auth::user();

        // Admin/Superviseur: accès complet
        if ($user->hasRoleCache('admin') || $user->hasRoleCache('superviseur') || $user->isSuperAdmin()) {
            return;
        }

        // Utilisateur normal: peut télécharger ses propres fiches
        if ($appel->user_id === $user->id) {
            return;
        }

        throw new AuthorizationException('Vous n\'avez pas accès à cette fiche');
    }
}
