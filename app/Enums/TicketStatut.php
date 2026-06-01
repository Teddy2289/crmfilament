<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketStatut: string implements HasLabel, HasColor, HasIcon
{
    case AppelRecu                    = 'appel_recu';
    case EnQualification              = 'en_qualification';
    case FicheComplete                = 'fiche_complete';
    case FicheIncomplete              = 'fiche_incomplete';
    case RdvPlanifie                  = 'rdv_planifie';
    case RappelPromis                 = 'rappel_promis';
    case EnAttenteConfirmationArtisan = 'en_attente_confirmation_artisan';
    case ArtisanConfirme              = 'artisan_confirme';
    case InterventionRealisee         = 'intervention_realisee';
    case ClotureSatisfait             = 'cloture_satisfait';
    case SuiviQualiteRequis           = 'suivi_qualite_requis';
    case ReclamationOuverte           = 'reclamation_ouverte';
    case P8EnTraitement               = 'p8_en_traitement';
    case DossierCloture               = 'dossier_cloture';

    public function getLabel(): ?string { return $this->label(); }
    public function label(): string
    {
        return match($this) {
            self::AppelRecu                    => 'Appel reçu',
            self::EnQualification              => 'En qualification',
            self::FicheComplete                => 'Fiche complète',
            self::FicheIncomplete              => 'Fiche incomplète',
            self::RdvPlanifie                  => 'RDV planifié',
            self::RappelPromis                 => 'Rappel promis',
            self::EnAttenteConfirmationArtisan => 'En attente confirmation artisan',
            self::ArtisanConfirme              => 'Artisan confirmé',
            self::InterventionRealisee         => 'Intervention réalisée',
            self::ClotureSatisfait             => 'Clôture satisfait',
            self::SuiviQualiteRequis           => 'Suivi qualité requis',
            self::ReclamationOuverte           => 'Réclamation ouverte',
            self::P8EnTraitement               => 'P8 en traitement',
            self::DossierCloture               => 'Dossier clôturé',
        };
    }

    public function getColor(): string|array|null { return $this->color(); }
    public function color(): string
    {
        return match($this) {
            self::AppelRecu                    => 'info',
            self::EnQualification              => 'warning',
            self::FicheComplete                => 'success',
            self::FicheIncomplete              => 'danger',
            self::RdvPlanifie                  => 'primary',
            self::RappelPromis                 => 'orange',
            self::EnAttenteConfirmationArtisan => 'purple',
            self::ArtisanConfirme              => 'success',
            self::InterventionRealisee         => 'teal',
            self::ClotureSatisfait             => 'emerald',
            self::SuiviQualiteRequis           => 'yellow',
            self::ReclamationOuverte           => 'red',
            self::P8EnTraitement               => 'amber',
            self::DossierCloture               => 'gray',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }
    public function icon(): string
    {
        return match($this) {
            self::AppelRecu                    => 'heroicon-o-phone-arrow-down-left',
            self::EnQualification              => 'heroicon-o-magnifying-glass',
            self::FicheComplete                => 'heroicon-o-document-check',
            self::FicheIncomplete              => 'heroicon-o-document-minus',
            self::RdvPlanifie                  => 'heroicon-o-calendar',
            self::RappelPromis                 => 'heroicon-o-phone-arrow-up-right',
            self::EnAttenteConfirmationArtisan => 'heroicon-o-clock',
            self::ArtisanConfirme              => 'heroicon-o-check-badge',
            self::InterventionRealisee         => 'heroicon-o-wrench-screwdriver',
            self::ClotureSatisfait             => 'heroicon-o-face-smile',
            self::SuiviQualiteRequis           => 'heroicon-o-clipboard-document-check',
            self::ReclamationOuverte           => 'heroicon-o-exclamation-triangle',
            self::P8EnTraitement               => 'heroicon-o-cog',
            self::DossierCloture               => 'heroicon-o-archive-box',
        };
    }

    public function estActif(): bool
    {
        return !in_array($this, [self::DossierCloture, self::ClotureSatisfait]);
    }

    public function estBloquant(): bool
    {
        return in_array($this, [self::FicheIncomplete, self::ReclamationOuverte, self::SuiviQualiteRequis]);
    }

    public function ordre(): int
    {
        return match($this) {
            self::AppelRecu                    => 1,
            self::EnQualification              => 2,
            self::FicheComplete                => 3,
            self::FicheIncomplete              => 4,
            self::RdvPlanifie                  => 5,
            self::RappelPromis                 => 6,
            self::EnAttenteConfirmationArtisan => 7,
            self::ArtisanConfirme              => 8,
            self::InterventionRealisee         => 9,
            self::ClotureSatisfait             => 10,
            self::SuiviQualiteRequis           => 11,
            self::ReclamationOuverte           => 12,
            self::P8EnTraitement               => 13,
            self::DossierCloture               => 14,
        };
    }

    public function statutsSuivants(): array
    {
        return match($this) {
            self::AppelRecu                    => [self::EnQualification],
            self::EnQualification              => [self::FicheComplete, self::FicheIncomplete],
            self::FicheComplete                => [self::RdvPlanifie, self::RappelPromis],
            self::FicheIncomplete              => [self::EnQualification],
            self::RdvPlanifie                  => [self::EnAttenteConfirmationArtisan, self::ArtisanConfirme],
            self::RappelPromis                 => [self::EnQualification],
            self::EnAttenteConfirmationArtisan => [self::ArtisanConfirme],
            self::ArtisanConfirme              => [self::InterventionRealisee],
            self::InterventionRealisee         => [self::ClotureSatisfait, self::SuiviQualiteRequis, self::ReclamationOuverte],
            self::ClotureSatisfait             => [self::DossierCloture],
            self::SuiviQualiteRequis           => [self::P8EnTraitement, self::DossierCloture],
            self::ReclamationOuverte           => [self::P8EnTraitement],
            self::P8EnTraitement               => [self::DossierCloture],
            self::DossierCloture               => [],
        };
    }
}
