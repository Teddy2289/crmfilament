<?php

namespace App\Http\Requests\Api\V1\RendezVous;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRendezVousRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a rendez-vous.
     *
     * Requirement 9.5: date_fin must be after date_heure (date_debut).
     * All fields are optional (PATCH semantics).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'                 => ['sometimes', 'string', Rule::enum(RendezVousType::class)],
            'statut'               => ['sometimes', 'nullable', 'string', Rule::enum(RendezVousStatut::class)],
            'date_heure'           => ['sometimes', 'date'],
            'date_fin'             => ['sometimes', 'nullable', 'date', 'after:date_heure'],
            'lieu'                 => ['sometimes', 'nullable', 'string', 'max:255'],
            'adresse_lieu'         => ['sometimes', 'nullable', 'string', 'max:500'],
            'interlocuteur_nom'    => ['sometimes', 'nullable', 'string', 'max:255'],
            'interlocuteur_tel'    => ['sometimes', 'nullable', 'string', 'max:50'],
            'interlocuteur_email'  => ['sometimes', 'nullable', 'email', 'max:255'],
            'notes'                => ['sometimes', 'nullable', 'string'],
            'commercial_id'        => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'teleprospecteur_id'   => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'rdvable_type'         => ['sometimes', 'nullable', 'string'],
            'rdvable_id'           => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_fin.after' => 'date_fin must be after date_debut',
        ];
    }
}
