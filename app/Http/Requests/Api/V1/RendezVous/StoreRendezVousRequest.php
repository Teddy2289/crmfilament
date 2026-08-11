<?php

namespace App\Http\Requests\Api\V1\RendezVous;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRendezVousRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a rendez-vous.
     *
     * Requirement 9.5: date_fin must be after date_debut.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'                 => ['required', 'string', Rule::enum(RendezVousType::class)],
            'statut'               => ['nullable', 'string', Rule::enum(RendezVousStatut::class)],
            'date_heure'           => ['required', 'date'],
            'date_fin'             => ['nullable', 'date', 'after:date_heure'],
            'lieu'                 => ['nullable', 'string', 'max:255'],
            'adresse_lieu'         => ['nullable', 'string', 'max:500'],
            'interlocuteur_nom'    => ['nullable', 'string', 'max:255'],
            'interlocuteur_tel'    => ['nullable', 'string', 'max:50'],
            'interlocuteur_email'  => ['nullable', 'email', 'max:255'],
            'notes'                => ['nullable', 'string'],
            'commercial_id'        => ['nullable', 'integer', 'exists:users,id'],
            'teleprospecteur_id'   => ['nullable', 'integer', 'exists:users,id'],
            'rdvable_type'         => ['nullable', 'string'],
            'rdvable_id'           => ['nullable', 'integer'],
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
