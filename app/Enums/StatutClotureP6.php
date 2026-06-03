<?php

namespace App\Enums;

enum StatutClotureP6: string
{
    case Satisfait          = 'satisfait';
    case SuiviQualiteRequis = 'suivi_qualite_requis';
    case ReclamationOuverte = 'reclamation_ouverte';

    public function label(): string
    {
        return match($this) {
            self::Satisfait          => 'Satisfait',
            self::SuiviQualiteRequis => 'Suivi qualité requis',
            self::ReclamationOuverte => 'Réclamation ouverte',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Satisfait          => 'success',
            self::SuiviQualiteRequis => 'warning',
            self::ReclamationOuverte => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Satisfait          => 'heroicon-o-face-smile',
            self::SuiviQualiteRequis => 'heroicon-o-clipboard-document-check',
            self::ReclamationOuverte => 'heroicon-o-exclamation-triangle',
        };
    }

    public function necessiteP8(): bool
    {
        return $this !== self::Satisfait;
    }

    public static function depuisNPS(int $note): self
    {
        return match(true) {
            $note >= 8 => self::Satisfait,
            $note >= 6 => self::SuiviQualiteRequis,
            default    => self::ReclamationOuverte,
        };
    }
}
