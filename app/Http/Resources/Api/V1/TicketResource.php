<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'reference'          => $this->reference,
            'statut'             => $this->statut?->value,
            'statut_label'       => $this->statut?->label(),
            'niveau_priorite'    => $this->niveau_priorite?->value,
            'niveau_priorite_label' => $this->niveau_priorite?->label(),
            'corps_de_metier'    => $this->corps_de_metier?->value,
            'corps_de_metier_label' => $this->corps_de_metier?->label(),
            'notes'              => $this->notes,
            'date_creation'      => $this->date_creation?->toIso8601String(),
            'date_cloture'       => $this->date_cloture?->toIso8601String(),
            'rdv_planifie_at'    => $this->rdv_planifie_at?->toIso8601String(),
            'rappel_promise_at'  => $this->rappel_promise_at?->toIso8601String(),
            'contact_particulier_id' => $this->contact_particulier_id,
            'artisan_id'         => $this->artisan_id,
            'operateur_id'       => $this->operateur_id,
            'created_by'         => $this->operateur_id,   // alias: operateur = created_by in this context
            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
