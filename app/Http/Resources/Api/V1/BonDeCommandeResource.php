<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BonDeCommandeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'numero'                    => $this->numero,
            'statut'                    => $this->statut?->value,
            'statut_label'              => $this->statut?->label(),
            'devis_id'                  => $this->devis_id,
            'ticket_id'                 => $this->ticket_id,
            'artisan_id'                => $this->artisan_id,
            'contact_particulier_id'    => $this->contact_particulier_id,
            'lignes'                    => $this->lignes,
            'montant_total_ttc'         => $this->montant_total_ttc,
            'acompte_montant'           => $this->acompte_montant,
            'acompte_encaisse'          => $this->acompte_encaisse,
            'date_intervention_prevue'  => $this->date_intervention_prevue?->toIso8601String(),
            'duree_estimee_heures'      => $this->duree_estimee_heures,
            'conditions_paiement'       => $this->conditions_paiement,
            'date_confirmation'         => $this->date_confirmation?->toIso8601String(),
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
