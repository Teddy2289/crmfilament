<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RendezVousResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Requirements: 12.3 (Enum values as strings)
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'type'                 => $this->type?->value,
            'type_label'           => $this->type?->label(),
            'statut'               => $this->statut?->value,
            'statut_label'         => $this->statut?->label(),
            'date_heure'           => $this->date_heure?->toIso8601String(),
            'lieu'                 => $this->lieu,
            'adresse_lieu'         => $this->adresse_lieu,
            'interlocuteur_nom'    => $this->interlocuteur_nom,
            'interlocuteur_tel'    => $this->interlocuteur_tel,
            'interlocuteur_email'  => $this->interlocuteur_email,
            'notes'                => $this->notes,
            'commercial_id'        => $this->commercial_id,
            'teleprospecteur_id'   => $this->teleprospecteur_id,
            'rdvable_type'         => $this->rdvable_type,
            'rdvable_id'           => $this->rdvable_id,
            'commercial'           => $this->when(
                $this->relationLoaded('commercial'),
                fn () => $this->commercial ? new UserMinimalResource($this->commercial) : null
            ),
            'created_at'           => $this->created_at?->toIso8601String(),
            'updated_at'           => $this->updated_at?->toIso8601String(),
        ];
    }
}
