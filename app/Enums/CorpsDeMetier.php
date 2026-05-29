<?php

namespace App\Enums;

enum CorpsDeMetier: string
{
    case Plomberie = 'Plomberie';
    case Electricite = 'Électricité';
    case Serrurerie = 'Serrurerie';
    case Menuiserie = 'Menuiserie';
    case Chauffage = 'Chauffage';
    case Climatisation = 'Climatisation';
    case Maconnerie = 'Maçonnerie';
    case Peinture = 'Peinture';
    case Vitrerie = 'Vitrerie';
    case Toiture = 'Toiture';
    case Electromenager = 'Électroménager';
    case MultiServices = 'Multi-services';

    public function label(): string
    {
        return $this->value;
    }

    public function color(): string
    {
        return match ($this) {
            self::Plomberie => 'blue',
            self::Electricite => 'yellow',
            self::Serrurerie => 'gray',
            self::Menuiserie => 'brown',
            self::Chauffage => 'red',
            self::Climatisation => 'cyan',
            self::Maconnerie => 'stone',
            self::Peinture => 'pink',
            self::Vitrerie => 'sky',
            self::Toiture => 'orange',
            self::Electromenager => 'purple',
            self::MultiServices => 'green',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Plomberie => 'heroicon-o-wrench',
            self::Electricite => 'heroicon-o-bolt',
            self::Serrurerie => 'heroicon-o-lock-closed',
            self::Menuiserie => 'heroicon-o-cube',
            self::Chauffage => 'heroicon-o-fire',
            self::Climatisation => 'heroicon-o-globe-alt',
            self::Maconnerie => 'heroicon-o-building-library',
            self::Peinture => 'heroicon-o-paint-brush',
            self::Vitrerie => 'heroicon-o-window',
            self::Toiture => 'heroicon-o-home',
            self::Electromenager => 'heroicon-o-tv',
            self::MultiServices => 'heroicon-o-cog-6-tooth',
        };
    }

    public function estPrioritaire(): bool
    {
        return in_array($this, self::metiersPrioritaires());
    }

    public static function metiersPrioritaires(): array
    {
        return [
            self::Plomberie,
            self::Electricite,
            self::Serrurerie,
        ];
    }

    public function questionsMetier(): array
    {
        return match ($this) {
            self::Plomberie => [
                'type_fuite' => 'Type de fuite ?',
                'localisation' => 'Où se situe la fuite ?',
                'robinet_ferme' => 'Robinet d\'arrivée fermé ?',
            ],
            self::Electricite => [
                'disjoncteur' => 'Le disjoncteur a-t-il sauté ?',
                'zone_concernee' => 'Quelle zone est concernée ?',
                'prise_surchauffee' => 'Prise surchauffée ?',
            ],
            self::Serrurerie => [
                'type_serrure' => 'Type de serrure ?',
                'cle_disponible' => 'Clés disponibles ?',
                'type_porte' => 'Type de porte ?',
            ],
            self::Chauffage => [
                'type_chauffage' => 'Type de chauffage ?',
                'marque_chaudiere' => 'Marque de la chaudière ?',
                'age_installation' => 'Âge de l\'installation ?',
            ],
            default => [],
        };
    }

    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
