<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatutCompteArtisan: string implements HasColor, HasIcon, HasLabel
{
    case EnAttenteActivation = 'en_attente_activation';
    case Actif = 'actif';
    case Suspendu = 'suspendu';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::EnAttenteActivation => "En attente d'activation",
            self::Actif => 'Actif',
            self::Suspendu => 'Suspendu',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::EnAttenteActivation => 'warning',
            self::Actif => 'success',
            self::Suspendu => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return $this->icon();
    }

    public function icon(): string
    {
        return match ($this) {
            self::EnAttenteActivation => 'heroicon-o-clock',
            self::Actif => 'heroicon-o-check-circle',
            self::Suspendu => 'heroicon-o-pause-circle',
        };
    }
}
