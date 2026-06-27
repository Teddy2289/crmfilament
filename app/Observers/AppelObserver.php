<?php

namespace App\Observers;

use App\Models\Appel;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Enums\ProspectStatut;

class AppelObserver
{
    /**
     * Handle the Appel "created" event.
     */
    public function created(Appel $appel): void
    {
        $this->updateProspectStatutFromPhoningResult($appel);
    }

    /**
     * Handle the Appel "updated" event.
     */
    public function updated(Appel $appel): void
    {
        // Si phoning_result a changé, mettre à jour le statut du prospect
        if ($appel->isDirty('phoning_result')) {
            $this->updateProspectStatutFromPhoningResult($appel);
        }
    }

    /**
     * Met à jour le statut du prospect basé sur le résultat phoning de l'appel.
     */
    protected function updateProspectStatutFromPhoningResult(Appel $appel): void
    {
        // Vérifier si l'appel est lié à un prospect
        if ($appel->appelable_type !== Prospect::class || !$appel->phoning_result) {
            return;
        }

        $prospect = $appel->appelable;
        if (!$prospect) {
            return;
        }

        // Récupérer le statut phoning correspondant
        $statutPhoning = StatutPhoning::where('model_type', 'prospect')
            ->where('code', $appel->phoning_result)
            ->where('actif', true)
            ->first();

        if (!$statutPhoning || !$statutPhoning->pipeline_statut) {
            return;
        }

        // Mettre à jour le statut du prospect
        try {
            $newStatut = ProspectStatut::from($statutPhoning->pipeline_statut);
            $prospect->update(['statut' => $newStatut]);
        } catch (\ValueError $e) {
            // Le statut pipeline n'est pas valide, ignorer
            \Log::warning('Statut pipeline invalide pour transition phoning', [
                'statut_phoning' => $statutPhoning->pipeline_statut,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the Appel "deleted" event.
     */
    public function deleted(Appel $appel): void
    {
        //
    }

    /**
     * Handle the Appel "restored" event.
     */
    public function restored(Appel $appel): void
    {
        //
    }

    /**
     * Handle the Appel "force deleted" event.
     */
    public function forceDeleted(Appel $appel): void
    {
        //
    }
}
