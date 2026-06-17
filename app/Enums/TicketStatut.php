<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TicketStatut: string implements HasColor, HasIcon, HasLabel
{
    case AppelRecu = 'appel_recu';
    case EnQualification = 'en_qualification';
    case FicheComplete = 'fiche_complete';
    case FicheIncomplete = 'fiche_incomplete';
    case UrgenceDetectee = 'urgence_detectee';        // P5 : bascule urgence détectée en P2
    case RdvPlanifie = 'rdv_planifie';
    case RappelPromis = 'rappel_promis';
    case EnAttenteConfirmationArtisan = 'en_attente_confirmation_artisan';
    case ArtisanConfirme = 'artisan_confirme';
    case DevisEnAttente = 'devis_en_attente';
    case DevisAccepte = 'devis_accepte';
    case InterventionRealisee = 'intervention_realisee';
    case FactureEmise = 'facture_emise';
    case PaiementRecu = 'paiement_recu';
    case ClotureSatisfait = 'cloture_satisfait';
    case SuiviQualiteRequis = 'suivi_qualite_requis';
    case ReclamationOuverte = 'reclamation_ouverte';
    case P8EnTraitement = 'p8_en_traitement';
    case DossierCloture = 'dossier_cloture';

    public function getLabel(): ?string
    {
        return $this->label();
    }

    public function label(): string
    {
        return match ($this) {
            self::AppelRecu => 'Appel reçu',
            self::EnQualification => 'En qualification',
            self::FicheComplete => 'Fiche complète',
            self::FicheIncomplete => 'Fiche incomplète',
            self::UrgenceDetectee => 'Urgence détectée',
            self::RdvPlanifie => 'RDV planifié',
            self::RappelPromis => 'Rappel promis',
            self::EnAttenteConfirmationArtisan => 'En attente confirmation artisan',
            self::ArtisanConfirme => 'Artisan confirmé',
            self::DevisEnAttente => 'Devis en attente',
            self::DevisAccepte => 'Devis accepté',
            self::InterventionRealisee => 'Intervention réalisée',
            self::FactureEmise => 'Facture émise',
            self::PaiementRecu => 'Paiement reçu',
            self::ClotureSatisfait => 'Clôture satisfait',
            self::SuiviQualiteRequis => 'Suivi qualité requis',
            self::ReclamationOuverte => 'Réclamation ouverte',
            self::P8EnTraitement => 'P8 en traitement',
            self::DossierCloture => 'Dossier clôturé',
        };
    }

    public function getColor(): string|array|null
    {
        return $this->color();
    }

    public function color(): string
    {
        return match ($this) {
            self::AppelRecu => 'info',
            self::EnQualification => 'warning',
            self::FicheComplete => 'success',
            self::FicheIncomplete => 'danger',
            self::UrgenceDetectee => 'red',
            self::RdvPlanifie => 'primary',
            self::RappelPromis => 'orange',
            self::EnAttenteConfirmationArtisan => 'purple',
            self::ArtisanConfirme => 'success',
            self::DevisEnAttente => 'warning',
            self::DevisAccepte => 'success',
            self::InterventionRealisee => 'teal',
            self::FactureEmise => 'indigo',
            self::PaiementRecu => 'emerald',
            self::ClotureSatisfait => 'emerald',
            self::SuiviQualiteRequis => 'yellow',
            self::ReclamationOuverte => 'red',
            self::P8EnTraitement => 'amber',
            self::DossierCloture => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return $this->icon();
    }

    public function icon(): string
    {
        return match ($this) {
            self::AppelRecu => 'heroicon-o-phone-arrow-down-left',
            self::EnQualification => 'heroicon-o-magnifying-glass',
            self::FicheComplete => 'heroicon-o-document-check',
            self::FicheIncomplete => 'heroicon-o-document-minus',
            self::UrgenceDetectee => 'heroicon-o-exclamation-circle',
            self::RdvPlanifie => 'heroicon-o-calendar',
            self::RappelPromis => 'heroicon-o-phone-arrow-up-right',
            self::EnAttenteConfirmationArtisan => 'heroicon-o-clock',
            self::ArtisanConfirme => 'heroicon-o-check-badge',
            self::DevisEnAttente => 'heroicon-o-document-text',
            self::DevisAccepte => 'heroicon-o-document-check',
            self::InterventionRealisee => 'heroicon-o-wrench-screwdriver',
            self::FactureEmise => 'heroicon-o-receipt-percent',
            self::PaiementRecu => 'heroicon-o-credit-card',
            self::ClotureSatisfait => 'heroicon-o-face-smile',
            self::SuiviQualiteRequis => 'heroicon-o-clipboard-document-check',
            self::ReclamationOuverte => 'heroicon-o-exclamation-triangle',
            self::P8EnTraitement => 'heroicon-o-cog',
            self::DossierCloture => 'heroicon-o-archive-box',
        };
    }

    public function estActif(): bool
    {
        return ! in_array($this, [
            self::DossierCloture,
            self::ClotureSatisfait,
        ]);
    }

    public function estBloquant(): bool
    {
        return in_array($this, [
            self::FicheIncomplete,
            self::ReclamationOuverte,
            self::SuiviQualiteRequis,
        ]);
    }

    public function estEnPhaseFinanciere(): bool
    {
        return in_array($this, [
            self::DevisEnAttente,
            self::DevisAccepte,
            self::FactureEmise,
            self::PaiementRecu,
        ]);
    }

    public function estEnAttenteClient(): bool
    {
        return in_array($this, [
            self::DevisEnAttente,
            self::RappelPromis,
        ]);
    }

    public function estEnAttenteArtisan(): bool
    {
        return in_array($this, [
            self::EnAttenteConfirmationArtisan,
            self::ArtisanConfirme,
        ]);
    }

    public function ordre(): int
    {
        return match ($this) {
            self::AppelRecu => 1,
            self::EnQualification => 2,
            self::FicheComplete => 3,
            self::FicheIncomplete => 4,
            self::UrgenceDetectee => 5,
            self::RdvPlanifie => 6,
            self::RappelPromis => 7,
            self::EnAttenteConfirmationArtisan => 8,
            self::ArtisanConfirme => 9,
            self::DevisEnAttente => 10,
            self::DevisAccepte => 11,
            self::InterventionRealisee => 12,
            self::FactureEmise => 13,
            self::PaiementRecu => 14,
            self::ClotureSatisfait => 15,
            self::SuiviQualiteRequis => 16,
            self::ReclamationOuverte => 17,
            self::P8EnTraitement => 18,
            self::DossierCloture => 19,
        };
    }

    public function progression(): int
    {
        return (int) round(($this->ordre() / 19) * 100);
    }

    /**
     * Matrice de transitions valides par statut.
     *
     * Flux principal : AppelRecu → EnQualification → FicheComplete/FicheIncomplete
     *   → UrgenceDetectee (P5) ou RdvPlanifie / RappelPromis
     *   → EnAttenteConfirmationArtisan → ArtisanConfirme
     *   → InterventionRealisee (direct) ou DevisEnAttente → DevisAccepte → InterventionRealisee
     *   → FactureEmise → PaiementRecu → ClotureSatisfait/DossierCloture
     * Flux qualité P6/P7 : InterventionRealisee → SuiviQualiteRequis → P8EnTraitement → DossierCloture
     * Flux P8 : ClotureSatisfait/SuiviQualiteRequis → ReclamationOuverte → P8EnTraitement → DossierCloture
     */
    public function statutsSuivants(): array
    {
        return match ($this) {
            self::AppelRecu => [self::EnQualification],
            self::EnQualification => [self::FicheComplete, self::FicheIncomplete],
            self::FicheComplete => [self::RdvPlanifie, self::RappelPromis, self::UrgenceDetectee],
            self::FicheIncomplete => [self::EnQualification],
            self::UrgenceDetectee => [self::EnAttenteConfirmationArtisan, self::RdvPlanifie],
            self::RdvPlanifie => [self::EnAttenteConfirmationArtisan],
            self::RappelPromis => [self::EnQualification, self::FicheComplete, self::RdvPlanifie],
            self::EnAttenteConfirmationArtisan => [self::ArtisanConfirme],
            self::ArtisanConfirme => [self::DevisEnAttente, self::InterventionRealisee],
            self::DevisEnAttente => [self::DevisAccepte, self::EnQualification],
            self::DevisAccepte => [self::InterventionRealisee],
            self::InterventionRealisee => [self::FactureEmise, self::ClotureSatisfait, self::SuiviQualiteRequis],
            self::FactureEmise => [self::PaiementRecu],
            self::PaiementRecu => [self::ClotureSatisfait, self::DossierCloture],
            self::ClotureSatisfait => [self::DossierCloture, self::ReclamationOuverte],
            self::SuiviQualiteRequis => [self::P8EnTraitement, self::ReclamationOuverte, self::DossierCloture],
            self::ReclamationOuverte => [self::P8EnTraitement],
            self::P8EnTraitement => [self::DossierCloture],
            self::DossierCloture => [],
        };
    }

    public static function statutsActifs(): array
    {
        return array_filter(self::cases(), fn ($statut) => $statut->estActif());
    }

    public static function statutsTerminaux(): array
    {
        return [
            self::DossierCloture,
            self::ClotureSatisfait,
        ];
    }

    public static function statutsCritiques(): array
    {
        return [
            self::FicheIncomplete,
            self::UrgenceDetectee,
            self::ReclamationOuverte,
            self::SuiviQualiteRequis,
        ];
    }

    public static function peutEmissionDevis(): array
    {
        return [
            self::ArtisanConfirme,
            self::RdvPlanifie,
        ];
    }
}
