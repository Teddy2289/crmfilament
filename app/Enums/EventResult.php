<?php

namespace App\Enums;

enum EventResult: string
{
    case Realise = 'Réalisé';
    case Annule = 'Annulé';
    case Decale = 'Décalé';
    case NonAbouti = 'Non abouti';
    case Rappel = 'Rappel';

    public function label(): string
    {
        return match($this) {
            self::Realise => 'Réalisé',
            self::Annule => 'Annulé',
            self::Decale => 'Décalé',
            self::NonAbouti => 'Non abouti',
            self::Rappel => 'Rappel',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Realise => 'success',
            self::Annule => 'danger',
            self::Decale => 'warning',
            self::NonAbouti => 'gray',
            self::Rappel => 'info',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Realise => 'heroicon-o-check-circle',
            self::Annule => 'heroicon-o-x-circle',
            self::Decale => 'heroicon-o-arrow-right-circle',
            self::NonAbouti => 'heroicon-o-minus-circle',
            self::Rappel => 'heroicon-o-phone-arrow-up-right',
        };
    }
}
