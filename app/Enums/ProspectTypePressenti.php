<?php

namespace App\Enums;

enum ProspectTypePressenti: string
{
    case CSE = 'CSE';
    case Syndicat = 'Syndicat';
    case Entreprise = 'Entreprise';
    case Association = 'Association';

    public function label(): string
    {
        return match($this) {
            self::CSE => 'CSE',
            self::Syndicat => 'Syndicat',
            self::Entreprise => 'Entreprise',
            self::Association => 'Association',
        };
    }
}
