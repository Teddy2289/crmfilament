<?php

namespace App\Filament\Shared\Components;

use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

/**
 * Source unique pour la configuration des champs téléphone du panel :
 * sélecteur de drapeau (France par défaut), saisie/affichage au format
 * national ("01 41 86 20 01") mais stockage en base au format
 * international E.164 ("+33141862001") pour distinguer un numéro
 * étranger d'un numéro français.
 *
 * Les numéros déjà en base (format national brut, sans indicatif) ne
 * sont pas migrés : ils coexistent avec le nouveau format jusqu'à leur
 * prochaine modification via ce champ.
 */
class PhoneNumberInput
{
    public static function make(string $name): PhoneInput
    {
        return PhoneInput::make($name)
            ->initialCountry('fr')
            ->defaultCountry('FR')
            ->disableLookup();
    }
}
