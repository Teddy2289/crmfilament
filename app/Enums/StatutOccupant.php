<?php

namespace App\Enums;

enum StatutOccupant: string
{
    case Proprietaire = 'Propriétaire';
    case Locataire = 'Locataire';
    case Gestionnaire = 'Gestionnaire';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match($this) {
            self::Proprietaire => 'primary',
            self::Locataire => 'warning',
            self::Gestionnaire => 'info',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Proprietaire => 'heroicon-o-key',
            self::Locataire => 'heroicon-o-user',
            self::Gestionnaire => 'heroicon-o-clipboard-document-list',
        };
    }
}
