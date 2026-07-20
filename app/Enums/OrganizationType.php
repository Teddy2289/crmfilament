<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum OrganizationType: string implements HasLabel
{
    case CSE = 'CSE';
    case Syndicat = 'Syndicat';
    case EntrepriseDirecte = 'Entreprise directe';
    case Association = 'Association';
    case PartenariatAnnule = 'Partenariat annulé';

    /**
     * Retourne un tableau pour les selects Filament
     */
    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    /**
     * Retourne un label plus lisible
     */
    public function label(): string
    {
        return match ($this) {
            self::CSE => 'CSE',
            self::Syndicat => 'Syndicat',
            self::EntrepriseDirecte => 'Entreprise directe',
            self::Association => 'Association',
            self::PartenariatAnnule => 'Partenariat annulé',
        };
    }

    /**
     * Requis par Filament\Support\Contracts\HasLabel pour que les Select
     * affichent ce label au lieu du nom brut du cas ("EntrepriseDirecte").
     */
    public function getLabel(): string
    {
        return $this->label();
    }

    /**
     * Couleur de badge — source unique pour que le "Type" affiche une
     * couleur différente pour chaque cas, y compris dans les tableaux
     * où il est affiché juste à côté du badge "Statut"
     * (voir App\Enums\OrganizationStatus::color()). Noms de couleur
     * Tailwind bruts, volontairement distincts de la palette du Statut.
     */
    public function color(): string
    {
        return match ($this) {
            self::CSE => 'violet',
            self::Syndicat => 'amber',
            self::EntrepriseDirecte => 'cyan',
            self::Association => 'emerald',
            self::PartenariatAnnule => 'rose',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::CSE => 'heroicon-o-building-office-2',
            self::Syndicat => 'heroicon-o-user-group',
            self::EntrepriseDirecte => 'heroicon-o-building-office',
            self::Association => 'heroicon-o-heart',
            self::PartenariatAnnule => 'heroicon-o-no-symbol',
        };
    }
}
