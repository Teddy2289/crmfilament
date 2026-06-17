<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TypeLogement: string implements HasColor, HasIcon, HasLabel
{
    case Maison = 'Maison';
    case Appartement = 'Appartement';
    case LocalCommercial = 'Local commercial';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function label(): string
    {
        return $this->value;
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Maison => 'success',
            self::Appartement => 'info',
            self::LocalCommercial => 'warning',
        };
    }

    public function color(): string
    {
        return $this->getColor();
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Maison => 'heroicon-o-home',
            self::Appartement => 'heroicon-o-building-office',
            self::LocalCommercial => 'heroicon-o-building-storefront',
        };
    }

    public function icon(): string
    {
        return $this->getIcon();
    }
}
