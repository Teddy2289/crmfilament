<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatutAffaireIntervention: string implements HasColor, HasIcon, HasLabel
{
    case EnAttente = 'en_attente';      // P3 : dispatch envoyé, artisan pas encore répondu
    case Confirmee = 'confirmee';       // P4 : artisan a confirmé sa venue
    case EnCours = 'en_cours';        // Artisan est sur place, intervention démarrée
    case Realisee = 'realisee';        // Travaux terminés, CR artisan soumis
    case ValideeClient = 'validee_client';  // Client a signé le bon d'intervention
    case Annulee = 'annulee';         // Annulée (artisan refuse / client annule)
    case Echec = 'echec';           // Artisan absent ou intervention impossible

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente confirmation',
            self::Confirmee => 'Confirmée',
            self::EnCours => 'En cours',
            self::Realisee => 'Réalisée',
            self::ValideeClient => 'Validée par le client',
            self::Annulee => 'Annulée',
            self::Echec => 'Échec',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Confirmee => 'primary',
            self::EnCours => 'info',
            self::Realisee => 'teal',
            self::ValideeClient => 'success',
            self::Annulee => 'gray',
            self::Echec => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return $this->icon();
    }

    public function icon(): string
    {
        return match ($this) {
            self::EnAttente => 'heroicon-o-clock',
            self::Confirmee => 'heroicon-o-check-circle',
            self::EnCours => 'heroicon-o-wrench-screwdriver',
            self::Realisee => 'heroicon-o-clipboard-document-check',
            self::ValideeClient => 'heroicon-o-hand-thumb-up',
            self::Annulee => 'heroicon-o-x-circle',
            self::Echec => 'heroicon-o-exclamation-triangle',
        };
    }

    public function estActive(): bool
    {
        return in_array($this, [self::EnAttente, self::Confirmee, self::EnCours]);
    }

    public function estTerminale(): bool
    {
        return in_array($this, [self::Realisee, self::ValideeClient, self::Annulee, self::Echec]);
    }

    public function estEchec(): bool
    {
        return in_array($this, [self::Annulee, self::Echec]);
    }

    public function statutsSuivants(): array
    {
        return match ($this) {
            self::EnAttente => [self::Confirmee, self::Annulee],
            self::Confirmee => [self::EnCours, self::Annulee],
            self::EnCours => [self::Realisee, self::Echec],
            self::Realisee => [self::ValideeClient],
            self::ValideeClient => [],
            self::Annulee => [],
            self::Echec => [],
        };
    }
}
