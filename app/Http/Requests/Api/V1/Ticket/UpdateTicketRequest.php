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
     * All fields nullable for partial updates.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'statut'                 => ['nullable', 'string', Rule::enum(TicketStatut::class)],
            'niveau_priorite'        => ['nullable', 'string', Rule::enum(NiveauPriorite::class)],
            'corps_de_metier'        => ['nullable', 'string', Rule::enum(CorpsDeMetier::class)],
            'notes'                  => ['nullable', 'string'],
            'contact_particulier_id' => ['nullable', 'integer', 'exists:contact_particuliers,id'],
        ];
    }
}
