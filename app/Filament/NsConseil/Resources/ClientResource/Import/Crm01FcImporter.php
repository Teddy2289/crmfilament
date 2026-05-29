<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

class Crm01FcImporter extends BaseClientImporter
{
    public static function getName(): string
    {
        return 'CRM 01FC';
    }

    public static function getRequiredColumns(): array
    {
        return ['Réf. client', 'Tiers', 'Département', 'Type du tiers', 'Provenance'];
    }

    protected function mapRow(array $row): array
    {
        return array_filter([
            'ref_client'        => trim((string) ($row['Réf. client'] ?? '')),
            'nom_tiers'         => trim((string) ($row['Tiers'] ?? '')),
            'telephone'         => trim((string) ($row['Téléphone'] ?? '')),
            'email'             => strtolower(trim((string) ($row['Email'] ?? ''))),
            'ville'             => trim((string) ($row['Ville'] ?? '')),
            'code_postal'       => trim((string) ($row['Code postal'] ?? '')),
            'departement'       => trim((string) ($row['Département'] ?? '')),
            'entreprise'        => trim((string) ($row['Entreprise'] ?? '')),
            'date_naissance'    => $this->parseDate($row['Date de naissance'] ?? null),
            'etat'              => $this->mapEtat($row['État'] ?? ''),
            'extra_data'        => $this->buildExtra($row),
        ], fn($v) => $v !== null && $v !== '');
    }

    private function buildExtra(array $row): ?array
    {
        $keys = [
            'Type du tiers',
            'Provenance',
            'Partenaire',
            'Consultant 1er Accueil',
            'Auteur',
            'Consultant Formateur',
            'Montant HT',
            'Dare de vente',           // typo présent dans le fichier source
            '(F) Date de lancement',
            'PDF',
            '(C) Date début de formation',
            '(C) Date de fin formation théorique',
            'Evaluation initiale',
            'Date certification',
            '(C) N° dossier EDOF',
            "Nombre d'heures de formation",
            'Heures réalisées',
            'Heures restantes',
            'Date envoi questionnaire à chaud',
        ];

        $extra = [];
        foreach ($keys as $key) {
            if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                $extra[$key] = $row[$key];
            }
        }

        return empty($extra) ? null : $extra;
    }
}