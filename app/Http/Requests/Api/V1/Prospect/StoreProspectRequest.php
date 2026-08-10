<?php

namespace App\Http\Requests\Api\V1\Prospect;

use App\Enums\ProspectStatut;
use App\Enums\ProspectTypePressenti;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProspectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nom'                 => ['required', 'string', 'max:255'],
            'type_pressenti'      => ['nullable', 'string', Rule::enum(ProspectTypePressenti::class)],
            'departement'         => ['nullable', 'string', 'max:3'],
            'telephone'           => ['nullable', 'string', 'max:20'],
            'telephone_alt'       => ['nullable', 'string', 'max:20'],
            'email'               => ['nullable', 'email', 'max:255'],
            'adresse'             => ['nullable', 'string'],
            'code_postal'         => ['nullable', 'string', 'max:5'],
            'ville'               => ['nullable', 'string', 'max:255'],
            'siret'               => ['nullable', 'string', 'min:14', 'max:14'],
            'secteur_activite'    => ['nullable', 'string'],
            'nb_salaries'         => ['nullable', 'integer', 'min:0'],
            'chiffre_affaires'    => ['nullable', 'numeric', 'min:0'],
            'statut'              => ['nullable', 'string', Rule::enum(ProspectStatut::class)],
            'teleprospecteur_id'  => ['nullable', 'integer', 'exists:users,id'],
            'commercial_id'       => ['nullable', 'integer', 'exists:users,id'],
            'campagne_id'         => ['nullable', 'integer', 'exists:campagne_phonings,id'],
            'description'         => ['nullable', 'string'],
            'difficile'           => ['nullable', 'boolean'],
        ];
    }
}
