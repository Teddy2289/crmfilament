<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CorpsDeMetier: string implements HasLabel, HasColor, HasIcon
{
    case Plomberie          = 'Plomberie';
    case Electricite        = 'Électricité';
    case Serrurerie         = 'Serrurerie';
    case Chauffage          = 'Chauffage';
    case Toiture            = 'Toiture';
    case Maconnerie         = 'Maçonnerie';
    case Peinture           = 'Peinture';
    case Menuiserie         = 'Menuiserie';
    case Carrelage          = 'Carrelage';
    case Vitrerie           = 'Vitrerie';
    case CanalAssainissement = 'Canalisation & Assainissement';
    case Renovation         = 'Rénovation';

    public function getLabel(): ?string { return $this->value; }
    public function label(): string     { return $this->value; }

    public function getColor(): string|array|null { return $this->color(); }
    public function color(): string
    {
        return match($this) {
            self::Plomberie          => 'blue',
            self::Electricite        => 'yellow',
            self::Serrurerie         => 'gray',
            self::Chauffage          => 'red',
            self::Toiture            => 'amber',
            self::Maconnerie         => 'stone',
            self::Peinture           => 'pink',
            self::Menuiserie         => 'orange',
            self::Carrelage          => 'lime',
            self::Vitrerie           => 'sky',
            self::CanalAssainissement => 'teal',
            self::Renovation         => 'emerald',
        };
    }

    public function getIcon(): ?string { return $this->icon(); }
    public function icon(): string
    {
        return match($this) {
            self::Plomberie          => 'heroicon-o-wrench',
            self::Electricite        => 'heroicon-o-bolt',
            self::Serrurerie         => 'heroicon-o-lock-closed',
            self::Chauffage          => 'heroicon-o-fire',
            self::Toiture            => 'heroicon-o-home',
            self::Maconnerie         => 'heroicon-o-building-library',
            self::Peinture           => 'heroicon-o-paint-brush',
            self::Menuiserie         => 'heroicon-o-cube',
            self::Carrelage          => 'heroicon-o-squares-2x2',
            self::Vitrerie           => 'heroicon-o-window',
            self::CanalAssainissement => 'heroicon-o-beaker',
            self::Renovation         => 'heroicon-o-building-office-2',
        };
    }

    public function estPrioritaire(): bool
    {
        return in_array($this, self::metiersPrioritaires());
    }

    public static function metiersPrioritaires(): array
    {
        return [self::Plomberie, self::Electricite, self::Serrurerie];
    }

    public function questionsMetier(): array
    {
        return match($this) {
            self::Plomberie   => [
                'type_fuite'      => 'Type de fuite ?',
                'localisation'    => 'Où se situe la fuite ?',
                'robinet_ferme'   => "Robinet d'arrivée fermé ?",
                'degats_associes' => 'Dégâts des eaux associés ?',
            ],
            self::Electricite => [
                'disjoncteur'     => 'Le disjoncteur a-t-il sauté ?',
                'zone_concernee'  => 'Quelle zone est concernée ?',
                'prise_surchauffee' => 'Prise surchauffée ou odeur de brûlé ?',
                'coupure_totale'  => 'Coupure totale ou partielle ?',
            ],
            self::Serrurerie  => [
                'type_serrure'    => 'Type de serrure ?',
                'cle_disponible'  => 'Clé disponible ?',
                'type_porte'      => 'Type de porte (blindée, standard, vitrée) ?',
                'effraction'      => 'Tentative d\'effraction ?',
            ],
            self::Chauffage   => [
                'type_chauffage'    => 'Type de chauffage ?',
                'marque_chaudiere'  => 'Marque et modèle de la chaudière ?',
                'age_installation'  => "Âge de l'installation ?",
                'code_erreur'       => 'Code erreur affiché ?',
            ],
            self::Toiture     => [
                'type_toiture'    => 'Type de toiture (tuiles, ardoises, zinc...) ?',
                'infiltration'    => 'Infiltration visible ?',
                'acces_toiture'   => 'Accès à la toiture possible ?',
                'surface_estimee' => 'Surface estimée à traiter ?',
            ],
            self::Maconnerie  => [
                'type_travaux'    => 'Type de travaux (fissures, reprise, enduit...) ?',
                'surface_estimee' => 'Surface estimée (m²) ?',
                'fissures'        => 'Fissures visibles ? (structurelles ou superficielles) ?',
                'humidite'        => 'Problème d\'humidité associé ?',
            ],
            self::Peinture    => [
                'type_surface'    => 'Type de surface (mur, plafond, façade) ?',
                'surface_m2'      => 'Surface estimée (m²) ?',
                'preparation'     => 'Préparation nécessaire (décrépissage, rebouchage) ?',
                'couleur_souhaitee' => 'Couleur souhaitée ?',
            ],
            self::Menuiserie  => [
                'type_menuiserie' => 'Type de menuiserie (fenêtre, porte, parquet...) ?',
                'materiau'        => 'Matériau concerné (bois, PVC, aluminium) ?',
                'degat_visible'   => 'Dégât visible (gonflement, pourriture, casse) ?',
                'dimension'       => 'Dimensions approximatives ?',
            ],
            self::Carrelage   => [
                'type_carrelage'  => 'Type de carrelage (sol, mur, extérieur) ?',
                'surface_m2'      => 'Surface concernée (m²) ?',
                'carreaux_casses' => 'Carreaux cassés ou décollés ?',
                'joint_a_refaire' => 'Joints à refaire ?',
            ],
            self::Vitrerie    => [
                'type_vitrage'    => 'Type de vitrage (simple, double, sécurité) ?',
                'bris_verre'      => 'Bris de verre ? (risque de coupure)',
                'dimension'       => 'Dimension approximative (L x H) ?',
                'menuiserie_abimee' => 'Menuiserie / cadre abîmé ?',
            ],
            self::CanalAssainissement => [
                'type_probleme'   => 'Type de problème (bouchon, fuite, odeur, débordement) ?',
                'localisation'    => 'Localisation (intérieur, extérieur, sous-sol) ?',
                'refoulement'     => 'Refoulement constaté ?',
                'odeurs'          => 'Odeurs nauséabondes ?',
            ],
            self::Renovation  => [
                'type_renovation' => 'Type de rénovation (cuisine, salle de bain, globale...) ?',
                'surface_m2'      => 'Surface totale (m²) ?',
                'delai_souhaite'  => 'Délai souhaité ?',
                'budget_indicatif' => 'Budget indicatif ?',
            ],
        };
    }

    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn($case) => [$case->value => $case->label()])
            ->toArray();
    }
}
