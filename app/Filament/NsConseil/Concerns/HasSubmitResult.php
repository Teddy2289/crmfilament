<?php

namespace App\Filament\NsConseil\Concerns;

use App\Models\Prospect;
use App\Services\Aopia\FicheGenerationService;
use App\Services\Phoning\PhoningQueueBuilder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

/**
 * Concern : orchestration de la soumission d'un résultat d'appel.
 * Extrait de PhoningWorkflow pour atteindre ≤ 200 lignes.
 */
trait HasSubmitResult
{
    /**
     * Point d'entrée principal : valide le formulaire, déclenche l'aperçu email
     * si nécessaire, puis persiste le résultat et avance dans la file.
     */
    public function submitResult(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $this->contactType = $this->resolveContactType() ?? $this->contactType;

        $codesValides = $this->getStatusValidationCodes();

        $this->validate([
            'statut_resultat'        => 'required|in:' . implode(',', $codesValides),
            'commentaires'           => $this->commentaireRequis()
                ? 'required|string|min:5|max:2000'
                : 'nullable|string|max:2000',
            'interlocuteur_email'    => 'nullable|email',
            'email_general_standard' => 'nullable|email',
        ], [
            'commentaires.required' => $this->messageCommentaireObligatoire(),
        ]);

        // Aperçu email si nécessaire
        if ($this->shouldPreviewEmail() && ! $this->emailPreviewConfirmed) {
            $this->openEmailPreview();

            return;
        }

        if ($this->showEmailPreview && $this->emailPreviewConfirmed) {
            $this->showEmailPreview = false;
        }

        // Mise à jour du modèle contact selon son type
        match ($this->contactType) {
            'artisan'    => $this->updateArtisan(),
            'partenaire' => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
            'prospect'   => $this->updateProspect(),
            'client'     => $this->updateClient(),
            default      => null,
        };

        $this->enregistrerAppel();
        $this->dispatchFicheGenerationJob();

        // Auto-génération fiches Word par statut phoning
        if ($this->contactType === 'prospect' && $this->currentContact instanceof Prospect) {
            try {
                $ficheService = app(FicheGenerationService::class);
                $docs = $ficheService->genererAutoParStatut(
                    $this->statut_resultat,
                    $this->currentContact,
                    $this->currentContact->rendezVous()->latest('date_heure')->first()
                );
                if (! empty($docs)) {
                    $noms = collect($docs)->pluck('nom_fichier')->implode(', ');
                    Notification::make()->title('Fiches générées automatiquement')->body($noms)->info()->send();
                }
            } catch (\Throwable) {
                // Ne pas bloquer le workflow
            }
        }

        Notification::make()
            ->title('Contact enregistré')
            ->body('Statut : ' . $this->getResultLabel())
            ->success()
            ->send();

        // Libérer la réservation cache
        if ($this->currentContact && $this->contactType) {
            $contactId = is_array($this->currentContact)
                ? (int) ($this->currentContact['id'] ?? 0)
                : (int) $this->currentContact->getKey();

            if ($contactId > 0) {
                app(PhoningQueueBuilder::class)->releaseQueueReservationForUser(
                    Auth::id(),
                    $this->contactType,
                    $contactId,
                );
            }
        }

        $this->resetEmailPreviewState();

        $this->maybeRequeueCurrentContactAfterResult();

        $this->completed++;

        $this->checkCampagneCompletion();
        $this->loadNextContact();
    }

    /**
     * Détermine si un aperçu email doit être affiché avant envoi.
     */
    protected function shouldPreviewEmail(): bool
    {
        return $this->contactType === 'prospect'
            && $this->currentContact instanceof Prospect
            && $this->getEmailPreviewPayload() !== null;
    }

    protected function maybeRequeueCurrentContactAfterResult(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $this->contactType = $this->resolveContactType() ?? $this->contactType;
        if (! $this->contactType) {
            return;
        }

        $status = $this->getSelectedStatus();
        if (! $status) {
            return;
        }

        if ($status->retire_de_file || ! $status->compte_comme_tentative) {
            return;
        }

        $campagneId = null;
        if (is_array($this->currentContact)) {
            $campagneId = isset($this->currentContact['campagne_id'])
                ? (int) $this->currentContact['campagne_id']
                : null;
        }

        if ($campagneId === null) {
            $campagneId = $this->currentCampagneId;
        }

        $contactId = is_array($this->currentContact)
            ? (int) ($this->currentContact['id'] ?? 0)
            : (int) $this->currentContact->getKey();

        if ($contactId <= 0) {
            return;
        }

        $this->contactQueue[] = [
            'type'        => $this->contactType,
            'id'          => $contactId,
            'campagne_id' => $campagneId,
        ];
    }

    protected function resolveContactType(): ?string
    {
        if ($this->contactType) {
            return $this->contactType;
        }

        if (! $this->currentContact) {
            return null;
        }

        return is_array($this->currentContact)
            ? ($this->currentContact['type'] ?? null)
            : null;
    }
}
