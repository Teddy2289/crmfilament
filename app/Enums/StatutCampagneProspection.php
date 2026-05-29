<?php

namespace App\Enums;

enum StatutCampagneProspection: string
{
    case AC = 'AC';       // À contacter
    case NR = 'NR';       // Non référencé
    case RP = 'RP';       // Réponse positive
    case OBJ = 'OBJ';     // Objection
    case SOC = 'SOC';     // Sous contrat/concurrent
    case HC = 'HC';       // Hors cible

    public function label(): string
    {
        return match($this) {
            self::AC => 'À contacter',
            self::NR => 'Non référencé',
            self::RP => 'Réponse positive',
            self::OBJ => 'Objection',
            self::SOC => 'Sous contrat concurrent',
            self::HC => 'Hors cible',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AC => 'gray',
            self::NR => 'info',
            self::RP => 'success',
            self::OBJ => 'warning',
            self::SOC => 'danger',
            self::HC => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::AC => 'heroicon-o-phone',
            self::NR => 'heroicon-o-question-mark-circle',
            self::RP => 'heroicon-o-hand-thumb-up',
            self::OBJ => 'heroicon-o-chat-bubble-left-right',
            self::SOC => 'heroicon-o-building-office',
            self::HC => 'heroicon-o-x-circle',
        };
    }

    public function estConvertible(): bool
    {
        return $this === self::RP;
    }

    public function estActif(): bool
    {
        return in_array($this, [self::AC, self::NR, self::RP, self::OBJ]);
    }

    public function statutsSuivants(): array
    {
        return match($this) {
            self::AC => [self::NR, self::RP, self::OBJ, self::SOC, self::HC],
            self::NR => [self::RP, self::OBJ, self::SOC, self::HC],
            self::RP => [],
            self::OBJ => [self::RP, self::SOC, self::HC],
            self::SOC => [],
            self::HC => [],
        };
    }
}
