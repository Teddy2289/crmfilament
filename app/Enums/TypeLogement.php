<?php

namespace App\Enums;

enum TypeLogement: string
{
    case Maison = 'Maison';
    case Appartement = 'Appartement';
    case LocalCommercial = 'Local commercial';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::Maison => 'success',
            self::Appartement => 'info',
            self::LocalCommercial => 'warning',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Maison => 'heroicon-o-home',
            self::Appartement => 'heroicon-o-building-office',
            self::LocalCommercial => 'heroicon-o-building-storefront',
        };
    }
}
