<?php

namespace App\Enums;

enum StatutCompteArtisan: string
{
    case EnAttenteActivation = 'en_attente_activation';
    case Actif = 'actif';
    case Suspendu = 'suspendu';

    public function label(): string
    {
        return match($this) {
            self::EnAttenteActivation => 'En attente d\'activation',
            self::Actif => 'Actif',
            self::Suspendu => 'Suspendu',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::EnAttenteActivation => 'warning',
            self::Actif => 'success',
            self::Suspendu => 'danger',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::EnAttenteActivation => 'heroicon-o-clock',
            self::Actif => 'heroicon-o-check-circle',
            self::Suspendu => 'heroicon-o-pause-circle',
        };
    }
}
