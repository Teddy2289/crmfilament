<?php

namespace App\Enums;

enum AncienneteProbleme: string
{
    case MoinsD1h = "Moins d'1h";
    case Aujourdhui = "Aujourd'hui";
    case DepuisQuelquesJours = 'Depuis quelques jours';
    case PlusDuneSemaine = "Plus d'une semaine";

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::MoinsD1h => 'danger',
            self::Aujourdhui => 'warning',
            self::DepuisQuelquesJours => 'info',
            self::PlusDuneSemaine => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::MoinsD1h => 'heroicon-o-clock',
            self::Aujourdhui => 'heroicon-o-calendar',
            self::DepuisQuelquesJours => 'heroicon-o-calendar-days',
            self::PlusDuneSemaine => 'heroicon-o-calendar-date-range',
        };
    }

    public function niveauUrgence(): int
    {
        return match ($this) {
            self::MoinsD1h => 1,
            self::Aujourdhui => 2,
            self::DepuisQuelquesJours => 3,
            self::PlusDuneSemaine => 4,
        };
    }
}
