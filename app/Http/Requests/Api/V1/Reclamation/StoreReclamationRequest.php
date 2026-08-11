<?php

namespace App\Http\Requests\Api\V1\Reclamation;

use App\Enums\StatutReclamation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReclamationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticket_id'               => ['required', 'integer', 'exists:tickets,id'],
            'description_reclamation' => ['required', 'string'],
            'statut'                  => ['nullable', 'string', Rule::enum(StatutReclamation::class)],
            'date_resolution_cible'   => ['nullable', 'date'],
            'notes_resolution'        => ['nullable', 'string'],
        ];
    }
}
