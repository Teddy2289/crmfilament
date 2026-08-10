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
            'id'               => $this->id,
            'reference'        => $this->reference,
            'statut'           => $this->statut?->value,
            'statut_label'     => $this->statut?->label(),
            'statut_color'     => $this->statut?->color(),
            'niveau_priorite'  => $this->niveau_priorite?->value,
            'priorite_label'   => $this->niveau_priorite?->label(),
            'corps_de_metier'  => $this->corps_de_metier?->value,
            'notes'            => $this->notes,
            'date_creation'    => $this->date_creation?->toIso8601String(),
            'date_cloture'     => $this->date_cloture?->toIso8601String(),
            'operateur_id'     => $this->operateur_id,
            'operateur'        => $this->when(
                $this->relationLoaded('operateur'),
                fn () => $this->operateur ? [
                    'id'     => $this->operateur->id,
                    'nom'    => $this->operateur->nom,
                    'prenom' => $this->operateur->prenom,
                ] : null
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
