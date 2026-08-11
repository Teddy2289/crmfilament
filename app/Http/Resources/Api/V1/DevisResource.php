<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'numero'                    => $this->numero,
            'statut'                    => $this->statut?->value,
            'statut_label'              => $this->statut?->label(),
            'ticket_id'                 => $this->ticket_id,
            'artisan_id'                => $this->artisan_id,
            'contact_particulier_id'    => $this->contact_particulier_id,
            'lignes'                    => $this->lignes,
            'remise_montant'            => $this->remise_montant,
            'remise_pourcentage'        => $this->remise_pourcentage,
            'conditions_paiement'       => $this->conditions_paiement,
            'notes'                     => $this->notes,
            'date_validite'             => $this->date_validite?->toDateString(),
            'date_emission'             => $this->date_emission?->toDateString(),
            'date_acceptation_refus'    => $this->date_acceptation_refus?->toIso8601String(),
            'mode_acceptation'          => $this->mode_acceptation,
            'total_ht'                  => $this->total_ht,
            'montant_tva'               => $this->montant_tva,
            'total_ttc'                 => $this->total_ttc,
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
