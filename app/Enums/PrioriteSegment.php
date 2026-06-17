<?php

namespace App\Enums;

enum PrioriteSegment: string
{
    case Haute = 'Haute';
    case Standard = 'Standard';
    case Basse = 'Basse';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Haute => 'danger',
            self::Standard => 'info',
            self::Basse => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Haute => 'heroicon-o-star',
            self::Standard => 'heroicon-o-flag',
            self::Basse => 'heroicon-o-arrow-down',
        };
    }

    public function delaiRecontactHeures(): int
    {
        return match ($this) {
            self::Haute => 24,
            self::Standard => 72,
            self::Basse => 168, // 1 semaine
        };
    }

    public static function depuisCorpsDeMetier(CorpsDeMetier $metier): self
    {
        return match ($metier) {
            CorpsDeMetier::Plomberie,
            CorpsDeMetier::Electricite,
            CorpsDeMetier::Serrurerie => self::Haute,
            CorpsDeMetier::Chauffage,
            CorpsDeMetier::Climatisation,
            CorpsDeMetier::Menuiserie => self::Standard,
            default => self::Basse,
        };
    }
}
