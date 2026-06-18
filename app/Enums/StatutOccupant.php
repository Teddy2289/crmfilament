<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatutOccupant: string implements HasColor, HasIcon, HasLabel
{
    case Proprietaire = 'Propriétaire';
    case Locataire = 'Locataire';
    case Gestionnaire = 'Gestionnaire';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Proprietaire => 'primary',
            self::Locataire => 'warning',
            self::Gestionnaire => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Proprietaire => 'heroicon-o-key',
            self::Locataire => 'heroicon-o-user',
            self::Gestionnaire => 'heroicon-o-clipboard-document-list',
        };
    }

    // Compatibilité avec le reste du code
    public function label(): string
    {
        return $this->getLabel();
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
