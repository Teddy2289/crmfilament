<?php

namespace App\Enums;

enum EventType: string
{
    case Appel = 'Appel';
    case Permanence = 'Permanence';
    case Presentation = 'Présentation';

    public function label(): string
    {
        return match($this) {
            self::Appel => 'Appel',
            self::Permanence => 'Permanence',
            self::Presentation => 'Présentation',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Appel => 'blue',
            self::Permanence => 'green',
            self::Presentation => 'purple',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Appel => 'heroicon-o-phone',
            self::Permanence => 'heroicon-o-building-office',
            self::Presentation => 'heroicon-o-presentation-chart-bar',
        };
    }
}
