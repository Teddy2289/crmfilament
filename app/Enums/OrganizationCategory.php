<?php

namespace App\Enums;

enum OrganizationCategory: string
{
    case Partenaires = 'partenaires';
    case Artisans = 'artisans';
    case Contrats = 'contrats';
    case FichesProspection = 'fiches_prospection';

    public function label(): string
    {
        return match ($this) {
            self::Partenaires => 'Partenaires',
            self::Artisans => 'Artisans',
            self::Contrats => 'Contrats',
            self::FichesProspection => 'Fiches prospection',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Partenaires => 'primary',
            self::Artisans => 'warning',
            self::Contrats => 'info',
            self::FichesProspection => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Partenaires => 'heroicon-o-user-group',
            self::Artisans => 'heroicon-o-wrench-screwdriver',
            self::Contrats => 'heroicon-o-document-text',
            self::FichesProspection => 'heroicon-o-document-duplicate',
        };
    }
}
