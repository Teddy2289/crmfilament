<?php

namespace App\Enums;

enum RendezVousType: string
{
    case Appel = 'Appel';
    case Permanence = 'Permanence';
    case Presentation = 'Présentation';
    case Intervention = 'Intervention';

    public function label(): string
    {
        return match($this) {
            self::Appel => 'Appel',
            self::Permanence => 'Permanence',
            self::Presentation => 'Présentation',
            self::Intervention => 'Intervention',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Appel => 'primary',
            self::Permanence => 'success',
            self::Presentation => 'warning',
            self::Intervention => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Appel => 'heroicon-o-phone',
            self::Permanence => 'heroicon-o-building-office-2',
            self::Presentation => 'heroicon-o-presentation-chart-bar',
            self::Intervention => 'heroicon-o-wrench-screwdriver',
        };
    }
}
