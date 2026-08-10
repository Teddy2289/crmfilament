<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartenaireResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'nom'                 => $this->nom,
            'entreprise'          => $this->entreprise,
            'nom_retenu'          => $this->nom_retenu,
            'siret'               => $this->siret,
            'type'                => $this->type?->value,
            'type_label'          => $this->type?->label(),
            'statut'              => $this->statut?->value,
            'statut_label'        => $this->statut?->label(),
            'statut_color'        => $this->statut?->color(),
            'telephone'           => $this->telephone,
            'email'               => $this->email,
            'adresse'             => $this->adresse,
            'code_postal'         => $this->code_postal,
            'ville'               => $this->ville,
            'departement'         => $this->departement,
            'secteur_activite'    => $this->secteur_activite,
            'nb_salaries'         => $this->nb_salaries,
            'chiffre_affaires'    => $this->chiffre_affaires,
            'date_signature'      => $this->date_signature?->toDateString(),
            'commercial'          => $this->when(
                $this->relationLoaded('commercial'),
                fn () => $this->commercial ? [
                    'id'     => $this->commercial->id,
                    'nom'    => $this->commercial->nom,
                    'prenom' => $this->commercial->prenom,
                ] : null
            ),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
