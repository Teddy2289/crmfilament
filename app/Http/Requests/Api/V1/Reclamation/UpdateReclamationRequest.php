<?php

namespace App\Http\Requests\Api\V1\Reclamation;

use App\Enums\StatutReclamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'statut'                  => ['sometimes', 'nullable', 'string', Rule::enum(StatutReclamation::class)],
            'description_reclamation' => ['sometimes', 'nullable', 'string'],
            'date_resolution_cible'   => ['sometimes', 'nullable', 'date'],
            'date_resolution_effective' => ['sometimes', 'nullable', 'date'],
            'validation_superviseur'  => ['sometimes', 'nullable', 'boolean'],
            'superviseur_id'          => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'notes_resolution'        => ['sometimes', 'nullable', 'string'],
        ];
    }
}
