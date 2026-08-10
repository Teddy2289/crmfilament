<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Api\FieldPermissionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProspectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $request->user()?->role_cache ?? 'guest';
        $fp   = app(FieldPermissionService::class);

        $all = [
            'id'               => $this->id,
            'nom'              => $this->nom,
            'statut'           => $this->statut?->value,
            'statut_label'     => $this->statut?->label(),
            'siret'            => $this->siret,
            'telephone'        => $this->telephone,
            'telephone_alt'    => $this->telephone_alt,
            'email'            => $this->email,
            'adresse'          => $this->adresse,
            'code_postal'      => $this->code_postal,
            'ville'            => $this->ville,
            'departement'      => $this->departement,
            'type_pressenti'   => $this->type_pressenti,
            'nb_salaries'      => $this->nb_salaries,
            'chiffre_affaires' => $this->chiffre_affaires,
            'difficile'        => $this->difficile,
            'qf_valide'        => $this->qf_valide,
            'campagne_id'      => $this->campagne_id,
            'commercial'       => new UserMinimalResource($this->whenLoaded('commercial')),
            'teleprospecteur'  => new UserMinimalResource($this->whenLoaded('teleprospecteur')),
            'campagne'         => $this->when(
                $this->relationLoaded('campagne'),
                fn () => $this->campagne ? ['id' => $this->campagne->id, 'nom' => $this->campagne->nom] : null
            ),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];

        return $fp->filterFields($all, $role, 'prospects', 'view');
    }
}
