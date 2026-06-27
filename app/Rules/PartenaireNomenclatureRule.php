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
     * Format attendu: [Type] [Entreprise] [Ville]
     * Exemple: "CSE MonEntreprise Paris"
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            $fail('Le nom du partenaire est requis.');
            return;
        }

        // Séparer le nom en parties
        $parts = explode(' ', trim($value));

        // Vérifier qu'il y a au moins 3 parties (Type, Entreprise, Ville)
        if (count($parts) < 3) {
            $fail('Le nom doit suivre le format: [Type] [Entreprise] [Ville]. Exemple: "CSE MonEntreprise Paris"');
            return;
        }

        // Vérifier que le premier mot correspond à un type valide
        $type = $parts[0];
        $validTypes = collect(OrganizationType::cases())
            ->map(fn ($case) => $case->value)
            ->toArray();

        if (!in_array($type, $validTypes)) {
            $fail('Le type doit être l\'un des suivants: ' . implode(', ', $validTypes));
        }

        // Vérifier que le nom de l'entreprise n'est pas vide
        $entreprise = $parts[1];
        if (empty($entreprise)) {
            $fail('Le nom de l\'entreprise est requis.');
        }

        // Vérifier que la ville n'est pas vide
        $ville = implode(' ', array_slice($parts, 2));
        if (empty($ville)) {
            $fail('La ville est requise.');
        }
    }
}
