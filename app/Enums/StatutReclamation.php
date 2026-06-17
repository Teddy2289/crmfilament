<?php

namespace App\Enums;

enum StatutReclamation: string
{
    case Ouverte = 'ouverte';
    case EnTraitement = 'en_traitement';
    case ValideeSuperviseur = 'validee_superviseur';
    case Cloturee = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::Ouverte => 'Ouverte',
            self::EnTraitement => 'En traitement',
            self::ValideeSuperviseur => 'Validée superviseur',
            self::Cloturee => 'Clôturée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Ouverte => 'danger',
            self::EnTraitement => 'warning',
            self::ValideeSuperviseur => 'info',
            self::Cloturee => 'success',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ouverte => 'heroicon-o-exclamation-circle',
            self::EnTraitement => 'heroicon-o-cog-6-tooth',
            self::ValideeSuperviseur => 'heroicon-o-check-badge',
            self::Cloturee => 'heroicon-o-archive-box-x-mark',
        };
    }

    public function estActive(): bool
    {
        return in_array($this, [self::Ouverte, self::EnTraitement]);
    }

    public function statutsSuivants(): array
    {
        return match ($this) {
            self::Ouverte => [self::EnTraitement],
            self::EnTraitement => [self::ValideeSuperviseur, self::Cloturee],
            self::ValideeSuperviseur => [self::Cloturee],
            self::Cloturee => [],
        };
    }
}
