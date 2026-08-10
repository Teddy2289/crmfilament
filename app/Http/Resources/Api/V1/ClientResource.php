<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'ref_client'        => $this->ref_client,
            'civilite'          => $this->civilite,
            'prenom'            => $this->prenom,
            'nom_tiers'         => $this->nom_tiers,
            'email'             => $this->email,
            'telephone'         => $this->telephone,
            'adresse'           => $this->adresse,
            'code_postal'       => $this->code_postal,
            'ville'             => $this->ville,
            'departement'       => $this->departement,
            'region'            => $this->region,
            'date_naissance'    => $this->date_naissance?->toDateString(),
            'entreprise'        => $this->entreprise,
            'type_tiers'        => $this->type_tiers,
            'etat'              => $this->etat,
            'etat_label'        => Client::etatLabel($this->etat),
            'montant_cpf'       => $this->montant_cpf,
            'ne_plus_contacter' => $this->ne_plus_contacter,
            'partenaire_id'     => $this->partenaire_id,
            'partenaire'        => $this->when(
                $this->relationLoaded('partenaire'),
                fn () => $this->partenaire ? [
                    'id'  => $this->partenaire->id,
                    'nom' => $this->partenaire->nom,
                ] : null
            ),
            'commercial'        => $this->when(
                $this->relationLoaded('commercial'),
                fn () => $this->commercial ? [
                    'id'     => $this->commercial->id,
                    'nom'    => $this->commercial->nom,
                    'prenom' => $this->commercial->prenom,
                ] : null
            ),
            'created_at'        => $this->created_at?->toIso8601String(),
            'updated_at'        => $this->updated_at?->toIso8601String(),
        ];
    }
}
