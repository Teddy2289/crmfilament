<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampagnePhoningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Requirements: 12.3 (enum fields as strings)
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'nom'                       => $this->nom,
            'description'               => $this->description,
            'statut'                    => $this->statut,
            'statut_label'              => $this->statut_label,
            'type_entite'               => $this->type_entite,
            'type_entite_label'         => $this->type_entite_label,
            'date_debut'                => $this->date_debut?->toDateString(),
            'date_fin'                  => $this->date_fin?->toDateString(),
            'max_tentatives'            => $this->max_tentatives,
            'jours_refroidissement'     => $this->jours_refroidissement,
            'exclure_autres_campagnes'  => $this->exclure_autres_campagnes,
            'exclure_sans_telephone'    => $this->exclure_sans_telephone,
            'user_id'                   => $this->user_id,
            'created_at'                => $this->created_at?->toIso8601String(),
            'updated_at'                => $this->updated_at?->toIso8601String(),
        ];
    }
}
