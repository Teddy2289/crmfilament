<?php

namespace App\Enums;

enum ProspectStatut: string
{
    case AC = 'AC';
    case STD_NR = 'STD_NR';
    case STD_Joint = 'STD_Joint';
    case CSE_NR = 'CSE_NR';
    case RP = 'RP';
    case RPC = 'RPC';
    case KO = 'KO';
    case QF = 'QF';
    case REPONDEUR = 'Répondeur';
    case NRP = 'NRP';
    case FAX = 'FAX';
    case SUPP = 'SUPP';
    case CSE_NI = 'CSE-NI';
    case RAPL_ELU = 'RAPL-ELU';
    case RAPL_STD = 'RAPL-STD';
    case BLOC2 = 'BLOC2';
    case NCSE_50 = 'NCSE+50';

    public function label(): string
    {
        return match ($this) {
            self::AC => 'À contacter',
            self::STD_NR => 'Standard non répondu',
            self::STD_Joint => 'Standard joint',
            self::CSE_NR => 'CSE non répondu',
            self::RP => 'Rappel planifié',
            self::RPC => 'RDV à planifier / Contact qualifié',
            self::KO => 'Hors cible / Refus',
            self::QF => 'RDV qualifié',
            self::REPONDEUR => 'Répondeur',
            self::NRP => 'Pas de réponse/Prompt',
            self::FAX => 'Fax',
            self::SUPP => 'Supprimé',
            self::CSE_NI => 'CSE non intéressé',
            self::RAPL_ELU => 'Rappel - Élu',
            self::RAPL_STD => 'Rappel - Standard',
            self::BLOC2 => 'Bloqué 2',
            self::NCSE_50 => 'Non-CSE +50 ans',
        };
    }

    /**
     * Couleurs Tailwind brutes (comme App\Enums\OrganizationStatus::color())
     * pour que chacun des 8 statuts reste visuellement distinct : les
     * couleurs sémantiques du panel ('success'/'warning'/...) sont trop
     * peu nombreuses pour couvrir 8 cas sans doublon.
     */
    public function color(): string
    {
        return match ($this) {
            self::AC => 'gray',
            self::STD_NR => 'orange',
            self::STD_Joint => 'blue',
            self::CSE_NR => 'amber',
            self::RP => 'indigo',
            self::RPC => 'teal',
            self::KO => 'red',
            self::QF => 'green',
            self::REPONDEUR => 'purple',
            self::NRP => 'rose',
            self::FAX => 'fuchsia',
            self::SUPP => 'slate',
            self::CSE_NI => 'cyan',
            self::RAPL_ELU => 'lime',
            self::RAPL_STD => 'sky',
            self::BLOC2 => 'violet',
            self::NCSE_50 => 'emerald',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::AC => 'heroicon-o-phone',
            self::STD_NR => 'heroicon-o-building-office',
            self::STD_Joint => 'heroicon-o-check-badge',
            self::CSE_NR => 'heroicon-o-user-group',
            self::RP => 'heroicon-o-clock',
            self::RPC => 'heroicon-o-calendar-days',
            self::KO => 'heroicon-o-x-circle',
            self::QF => 'heroicon-o-check-circle',
            self::REPONDEUR => 'heroicon-o-speaker-wave',
            self::NRP => 'heroicon-o-minus-circle',
            self::FAX => 'heroicon-o-document',
            self::SUPP => 'heroicon-o-trash',
            self::CSE_NI => 'heroicon-o-hand-raised',
            self::RAPL_ELU => 'heroicon-o-users',
            self::RAPL_STD => 'heroicon-o-arrow-path',
            self::BLOC2 => 'heroicon-o-stop-circle',
            self::NCSE_50 => 'heroicon-o-chart-bar',
        };
    }

    /**
     * Explication en langage courant, affichée en infobulle au survol du
     * badge pour les utilisateurs qui ne connaissent pas le pipeline par
     * cœur.
     */
    public function description(): string
    {
        return match ($this) {
            self::AC => "Nouveau prospect, personne ne l'a encore appelé.",
            self::STD_NR => 'Le standard a été appelé mais personne n\'a répondu.',
            self::STD_Joint => 'Le standard a répondu, la qualification est en cours.',
            self::CSE_NR => 'Le CSE a été contacté mais n\'a pas répondu.',
            self::RP => 'Un rappel a été planifié avec ce prospect.',
            self::RPC => 'Contact qualifié : un rendez-vous reste à planifier.',
            self::KO => 'Hors cible ou refus : ce prospect ne sera pas poursuivi.',
            self::QF => 'Rendez-vous qualifié, prêt à être traité par un commercial.',
            self::REPONDEUR => 'Le prospect a un répondeur activé.',
            self::NRP => 'Pas de réponse ou invitation à laisser un message.',
            self::FAX => 'Le numéro contacté est un fax.',
            self::SUPP => 'Le prospect a été supprimé de la base.',
            self::CSE_NI => 'Le CSE a indiqué ne pas être intéressé.',
            self::RAPL_ELU => 'Rappel prévu avec un élu ou représentant.',
            self::RAPL_STD => 'Rappel auprès du standard pour relancer le contact.',
            self::BLOC2 => 'Le prospect est bloqué (deuxième blocage).',
            self::NCSE_50 => 'Non-CSE de plus de 50 ans.',
        };
    }

    /**
     * Matrice CDC AOPIA des transitions autorisées.
     * Le passage QF n'est pas ici : il passe par le service TL uniquement.
     *
     * @return list<self>
     */
    public function transitionsAutorisees(): array
    {
        return match ($this) {
            self::AC => [self::STD_NR, self::STD_Joint, self::KO],
            self::STD_NR => [self::STD_Joint, self::KO, self::AC],
            self::STD_Joint => [self::CSE_NR, self::RP, self::RPC, self::KO],
            self::CSE_NR => [self::RP, self::RPC, self::STD_Joint, self::KO],
            self::RP => [self::STD_Joint, self::CSE_NR, self::RPC, self::KO],
            self::RPC => [self::RP, self::KO],
            self::KO, self::QF => [],
        };
    }

    public function peutAllerVers(self $nouveauStatut): bool
    {
        return in_array($nouveauStatut, $this->transitionsAutorisees(), true);
    }

    public function estArchive(): bool
    {
        return $this === self::KO;
    }

    public function estQualifie(): bool
    {
        return $this === self::QF;
    }

    public function exigeRappel(): bool
    {
        return in_array($this, [self::STD_NR, self::CSE_NR, self::RP, self::RPC], true);
    }

    public static function pourSelect(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
