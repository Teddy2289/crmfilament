<?php

namespace App\Rules;

use App\Enums\OrganizationType;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PartenaireNomenclatureRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Format attendu: [Entreprise] [Ville] [Département] [Type]
     * Exemple: "MonEntreprise Paris 75 CSE"
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            $fail('Le nom retenu est requis.');
            return;
        }

        // Séparer le nom en parties
        $parts = explode(' ', trim($value));

        // Vérifier qu'il y a au moins 3 parties (Entreprise, Ville, Type)
        if (count($parts) < 3) {
            $fail('Le nom retenu doit suivre le format: [Entreprise] [Ville] [Département] [Type]. Exemple: "MonEntreprise Paris 75 CSE"');
            return;
        }

        // Vérifier que le dernier mot correspond à un type valide
        $type = end($parts);
        $validTypes = collect(OrganizationType::cases())
            ->map(fn ($case) => $case->value)
            ->toArray();

        if (!in_array($type, $validTypes)) {
            $fail('Le type doit être l\'un des suivants: ' . implode(', ', $validTypes));
        }

        // Vérifier que le nom de l'entreprise n'est pas vide
        $entreprise = $parts[0];
        if (empty($entreprise)) {
            $fail('Le nom de l\'entreprise est requis.');
        }
    }
}
