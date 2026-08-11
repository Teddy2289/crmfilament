<?php

namespace App\Http\Requests\Api\V1\Prospect;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest for POST /api/v1/prospects/{prospect}/appel
 *
 * Requirements: 10.3, 10.4
 *
 * Validates the payload used to record a phoning call against a prospect.
 * Returns HTTP 422 automatically when statut_phoning_id does not exist in
 * the statut_phonings table.
 */
class EnregistrerAppelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by the controller via $this->authorize(),
     * so we always return true here.
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
            // Required — must reference an existing, active statut phoning (Req 10.4)
            'statut_phoning_id' => ['required', 'integer', 'exists:statut_phonings,id'],

            // Optional call metadata
            'commentaire'       => ['nullable', 'string', 'max:5000'],
            'duree_secondes'    => ['nullable', 'integer', 'min:0'],
            'date_heure'        => ['nullable', 'date'],
            'enregistrement_audio' => ['nullable', 'string', 'max:1000'],
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
            'statut_phoning_id.required' => 'statut_phoning_id invalide',
            'statut_phoning_id.integer'  => 'statut_phoning_id invalide',
            'statut_phoning_id.exists'   => 'statut_phoning_id invalide',
        ];
    }
}
