<?php

namespace App\Enums;

enum OrganizationStatus: string
{
    case AProspecter = 'a_prospecter';
    case EnCoursProspection = 'en_cours_prospection';
    case RdvEnCours = 'rdv_en_cours';
    case SigneAccordCadre = 'signe_accord_cadre';
    case ConventionEngagement = 'convention_engagement';
    case Refus = 'refus';

    public function label(): string
    {
        return match ($this) {
            self::AProspecter => 'À prospecter',
            self::EnCoursProspection => 'En cours de prospection',
            self::RdvEnCours => 'RDV en cours',
            self::SigneAccordCadre => 'Signé accord cadre',
            self::ConventionEngagement => 'Convention d\'engagement',
            self::Refus => 'Refus',
        };
    }

    /**
     * Couleur de badge — source unique utilisée par le tableau et la fiche
     * du Partenaire ainsi que par les vues Phoning/Kanban qui affichent le
     * statut, pour éviter que le même statut n'ait des couleurs différentes
     * selon l'écran.
     *
     * Ce sont volontairement des noms de couleur Tailwind "bruts" (et non les
     * clés sémantiques 'success'/'warning'/'danger' du panel Filament) : le
     * Kanban Partenaire (resources/views/livewire/partenaire-kanban.blade.php)
     * les interpole directement dans des classes Tailwind (`bg-{color}-100`),
     * qui n'existent que pour les vraies couleurs de la palette Tailwind.
     */
    public function color(): string
    {
        return match ($this) {
            self::AProspecter => 'gray',
            self::EnCoursProspection => 'blue',
            self::RdvEnCours => 'orange',
            self::SigneAccordCadre => 'green',
            self::ConventionEngagement => 'indigo',
            self::Refus => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AProspecter => 'heroicon-o-queue-list',
            self::EnCoursProspection => 'heroicon-o-phone',
            self::RdvEnCours => 'heroicon-o-calendar',
            self::SigneAccordCadre => 'heroicon-o-document-check',
            self::ConventionEngagement => 'heroicon-o-check-badge',
            self::Refus => 'heroicon-o-x-circle',
        };
    }

    public function estActif(): bool
    {
        return ! in_array($this, [self::Refus, self::ConventionEngagement]);
    }

    public function estBloque(): bool
    {
        return $this === self::Refus;
    }

    public function estConverti(): bool
    {
        return in_array($this, [self::SigneAccordCadre, self::ConventionEngagement]);
    }

    public function statutsSuivants(): array
    {
        return match ($this) {
            self::AProspecter => [self::EnCoursProspection, self::Refus],
            self::EnCoursProspection => [self::RdvEnCours, self::Refus],
            self::RdvEnCours => [self::SigneAccordCadre, self::EnCoursProspection, self::Refus],
            self::SigneAccordCadre => [self::ConventionEngagement, self::Refus],
            self::ConventionEngagement => [],
            self::Refus => [self::AProspecter],
        };
    }

    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn ($case) => [$case->value => $case->label()])
            ->toArray();
    }

    public static function statuses(): \Illuminate\Support\Collection
{
    return collect(self::cases())->map(fn (self $case) => [
        'id' => $case->value,
        'title' => $case->label(),
        'color' => $case->color(),
    ]);
}
}
