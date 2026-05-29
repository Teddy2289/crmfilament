<?php

namespace App\Enums;

enum RendezVousStatut: string
{
    case Planifie = 'Planifié';
    case Realise = 'Réalisé';
    case Annule = 'Annulé';
    case Decale = 'Décalé';

    public function label(): string
    {
        return match($this) {
            self::Planifie => 'Planifié',
            self::Realise => 'Réalisé',
            self::Annule => 'Annulé',
            self::Decale => 'Décalé',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Planifie => 'info',
            self::Realise => 'success',
            self::Annule => 'danger',
            self::Decale => 'warning',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::Planifie => 'heroicon-o-calendar',
            self::Realise => 'heroicon-o-check-circle',
            self::Annule => 'heroicon-o-x-circle',
            self::Decale => 'heroicon-o-arrow-path',
        };
    }
}
