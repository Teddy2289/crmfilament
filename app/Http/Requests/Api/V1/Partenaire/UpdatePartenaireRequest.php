<?php

namespace App\Http\Requests\Api\V1\Partenaire;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartenaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * All fields nullable for partial (PATCH) semantics.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom'              => ['sometimes', 'required', 'string', 'max:255'],
            'type'             => ['nullable', 'string', Rule::enum(OrganizationType::class)],
            'statut'           => ['nullable', 'string', Rule::enum(OrganizationStatus::class)],
            'entreprise'       => ['nullable', 'string', 'max:255'],
            'siret'            => ['nullable', 'string', 'min:14', 'max:14'],
            'telephone'        => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'adresse'          => ['nullable', 'string'],
            'code_postal'      => ['nullable', 'string', 'max:5'],
            'ville'            => ['nullable', 'string', 'max:255'],
            'departement'      => ['nullable', 'string', 'max:3'],
            'secteur_activite' => ['nullable', 'string'],
            'nb_salaries'      => ['nullable', 'integer', 'min:0'],
            'chiffre_affaires' => ['nullable', 'numeric', 'min:0'],
            'commercial_id'    => ['nullable', 'integer', 'exists:users,id'],
            'notes'            => ['nullable', 'string'],
        ];
    }
}
