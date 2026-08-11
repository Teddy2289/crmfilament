<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReclamationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                          => $this->id,
            'ticket_id'                   => $this->ticket_id,
            'statut'                      => $this->statut?->value,
            'statut_label'                => $this->statut?->label(),
            'description_reclamation'     => $this->description_reclamation,
            'date_ouverture'              => $this->date_ouverture?->toIso8601String(),
            'date_resolution_cible'       => $this->date_resolution_cible?->toDateString(),
            'date_resolution_effective'   => $this->date_resolution_effective?->toDateString(),
            'validation_superviseur'      => $this->validation_superviseur,
            'superviseur_id'              => $this->superviseur_id,
            'notes_resolution'            => $this->notes_resolution,
            'created_at'                  => $this->created_at?->toIso8601String(),
            'updated_at'                  => $this->updated_at?->toIso8601String(),
        ];
    }
}
