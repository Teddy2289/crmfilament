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

        $trimmed = trim($value);

        // Vérifier qu'il y a au moins 3 mots (Entreprise, Ville, Type)
        if (count(explode(' ', $trimmed)) < 3) {
            $fail('Le nom retenu doit suivre le format: [Entreprise] [Ville] [Département] [Type]. Exemple: "MonEntreprise Paris 75 CSE"');
            return;
        }

        // Le type valide qui termine la chaîne peut contenir plusieurs mots
        // (ex: "Entreprise directe", "Partenariat annulé") : on ne peut donc
        // pas se contenter du dernier mot, il faut tester le suffixe contre
        // chaque valeur possible, en gardant la correspondance la plus longue.
        $validTypes = collect(OrganizationType::cases())
            ->map(fn ($case) => $case->value)
            ->toArray();

        $matchedType = collect($validTypes)
            ->filter(fn ($type) => str_ends_with($trimmed, $type))
            ->sortByDesc(fn ($type) => strlen($type))
            ->first();

        if ($matchedType === null) {
            $fail('Le type doit être l\'un des suivants: '.implode(', ', $validTypes));

            return;
        }

        // Vérifier que le nom de l'entreprise (avant le type) n'est pas vide
        $entreprise = trim(substr($trimmed, 0, -strlen($matchedType)));
        if (empty($entreprise)) {
            $fail('Le nom de l\'entreprise est requis.');
        }
    }
}
