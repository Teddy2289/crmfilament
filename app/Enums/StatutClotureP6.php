<?php

namespace App\Enums;

enum StatutClotureP6: string
{
    case Satisfait = 'Satisfait';
    case SuiviQualiteRequis = 'Suivi qualité requis';
    case ReclamationOuverte = 'Réclamation ouverte';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::Satisfait => 'success',
            self::SuiviQualiteRequis => 'warning',
            self::ReclamationOuverte => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Satisfait => 'heroicon-o-face-smile',
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
        if ($note >= 8) {
            return self::Satisfait;
        } elseif ($note >= 6) {
            return self::SuiviQualiteRequis;
        } else {
            return self::ReclamationOuverte;
        }
    }
}
