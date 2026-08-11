<?php

namespace App\Http\Requests\Api\V1\Ticket;

use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use App\Enums\TicketStatut;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * All fields nullable/sometimes for partial (PATCH) semantics.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statut'                 => ['sometimes', 'nullable', 'string', Rule::enum(TicketStatut::class)],
            'niveau_priorite'        => ['sometimes', 'nullable', 'string', Rule::enum(NiveauPriorite::class)],
            'corps_de_metier'        => ['sometimes', 'nullable', 'string', Rule::enum(CorpsDeMetier::class)],
            'notes'                  => ['sometimes', 'nullable', 'string'],
            'contact_particulier_id' => ['sometimes', 'nullable', 'integer', 'exists:contact_particuliers,id'],
            'artisan_id'             => ['sometimes', 'nullable', 'integer', 'exists:artisans,id'],
            'rdv_planifie_at'        => ['sometimes', 'nullable', 'date'],
            'rappel_promise_at'      => ['sometimes', 'nullable', 'date'],
        ];
    }
}
