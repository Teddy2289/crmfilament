<?php

namespace App\Enums;

enum OrganizationCategory: string
{
    case Partenaires = 'partenaires';
    case Artisans = 'artisans';
    case Contrats = 'contrats';

    public function label(): string
    {
        return match ($this) {
            self::Partenaires => 'Partenaires',
            self::Artisans => 'Artisans',
            self::Contrats => 'Contrats',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Partenaires => 'primary',
            self::Artisans => 'warning',
            self::Contrats => 'info',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Partenaires => 'heroicon-o-user-group',
            self::Artisans => 'heroicon-o-wrench-screwdriver',
            self::Contrats => 'heroicon-o-document-text',
        };
    }
}
