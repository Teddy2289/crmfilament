<?php

namespace App\Enums;

enum StatutCampagneProspection: string
{
    case AC = 'AC';   // À contacter
    case NR = 'NR';   // Non Répondu (appel sans réponse)
    case RP = 'RP';   // Rappel Planifié (contacté, rappel planifié)
    case OBJ = 'OBJ';  // Objection (à lever)
    case SOC = 'SOC';  // Souscrit (accord verbal, prêt à convertir)
    case HC = 'HC';   // Hors cible
    case KO = 'KO';

    public function label(): string
    {
        return match ($this) {
            self::AC => 'À contacter',
            self::NR => 'Non Répondu',
            self::RP => 'Rappel Planifié',
            self::OBJ => 'Objection',
            self::SOC => 'Souscrit',
            self::HC => 'Hors cible',
            self::KO => 'K.O',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AC => 'gray',
            self::NR => 'info',
            self::RP => 'warning',
            self::OBJ => 'orange',
            self::SOC => 'success',
            self::HC => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AC => 'heroicon-o-phone',
            self::NR => 'heroicon-o-question-mark-circle',
            self::RP => 'heroicon-o-calendar',
            self::OBJ => 'heroicon-o-chat-bubble-left-right',
            self::SOC => 'heroicon-o-hand-thumb-up',
            self::HC => 'heroicon-o-x-circle',
        };
    }

    /**
     * Un prospect SOC (accord verbal obtenu) peut être converti en artisan.
     */
    public function estConvertible(): bool
    {
        return $this === self::SOC;
    }

    /**
     * Statuts actifs = en cours de prospection (non terminaux).
     * SOC et HC sont terminaux.
     */
    public function estActif(): bool
    {
        return in_array($this, [self::AC, self::NR, self::RP, self::OBJ]);
    }

    /**
     * Matrice de transitions valides (pipeline Section 10.3).
     * AC → NR|RP|OBJ|SOC|HC
     * NR → RP|OBJ|SOC|HC
     * RP → OBJ|SOC|HC
     * OBJ → RP|SOC|HC
     * SOC → [] (terminal : artisan créé)
     * HC  → [] (terminal : hors cible)
     */
    public function statutsSuivants(): array
    {
        return match ($this) {
            self::AC => [self::NR, self::RP, self::OBJ, self::SOC, self::HC],
            self::NR => [self::RP, self::OBJ, self::SOC, self::HC],
            self::RP => [self::OBJ, self::SOC, self::HC],
            self::OBJ => [self::RP, self::SOC, self::HC],
            self::SOC => [],
            self::HC => [],
        };
    }
}
