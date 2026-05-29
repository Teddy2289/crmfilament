<?php

namespace App\Enums;

enum NiveauPriorite: string
{
    case Urgence = 'URGENCE';
    case Prioritaire = 'PRIORITAIRE';
    case Standard = 'STANDARD';

    public function label(): string
    {
        return match($this) {
            self::Urgence => 'Urgence',
            self::Prioritaire => 'Prioritaire',
            self::Standard => 'Standard',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Urgence => 'danger',
            self::Prioritaire => 'warning',
            self::Standard => 'info',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Urgence => 'heroicon-o-exclamation-circle',
            self::Prioritaire => 'heroicon-o-arrow-up-circle',
            self::Standard => 'heroicon-o-flag',
        };
    }

    public function delaiMaxMinutes(): int
    {
        return match($this) {
            self::Urgence => 30,
            self::Prioritaire => 120,
            self::Standard => 480,
        };
    }
}
