<?php

namespace App\Http\Requests\Api\V1\Ticket;

use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_particulier_id' => ['required', 'integer', 'exists:contact_particuliers,id'],
            'niveau_priorite'        => ['nullable', 'string', Rule::enum(NiveauPriorite::class)],
            'corps_de_metier'        => ['nullable', 'string', Rule::enum(CorpsDeMetier::class)],
            'notes'                  => ['nullable', 'string'],
            // operateur_id is injected from $request->user()->id in the controller
        ];
    }
}
