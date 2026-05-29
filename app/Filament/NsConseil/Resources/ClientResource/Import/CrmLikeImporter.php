<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

class CrmLikeImporter extends BaseClientImporter
{
    public static function getName(): string
    {
        return 'CRM LIKE';
    }

    public static function getRequiredColumns(): array
    {
        return ['Civilité', 'Réf. client', 'Partenaire Like'];
    }

    protected function mapRow(array $row): array
    {
        return array_filter([
            'civilite'          => trim((string) ($row['Civilité'] ?? '')),
            'ref_client'        => trim((string) ($row['Réf. client'] ?? '')),
            'nom_tiers'         => trim((string) ($row['Tiers'] ?? '')),
            'telephone'         => trim((string) ($row['Téléphone'] ?? '')),
            'email'             => strtolower(trim((string) ($row['Email'] ?? ''))),
            'ville'             => trim((string) ($row['Ville'] ?? '')),
            'code_postal'       => trim((string) ($row['Code postal'] ?? '')),
            'adresse'           => trim((string) ($row['Adresse'] ?? '')),
            'entreprise'        => trim((string) ($row['Entreprise'] ?? '')),
            'date_naissance'    => $this->parseDate($row['Date de naissance'] ?? null),
            'montant_cpf'       => $this->parseFloat($row['Montant CPF'] ?? null),
            'ne_plus_contacter' => $this->parseBool($row['Ne plus contacter'] ?? false),
            'etat'              => $this->mapEtat($row['État'] ?? ''),
            'extra_data'        => $this->buildExtra($row),
        ], fn($v) => $v !== null && $v !== '');
    }

    private function buildExtra(array $row): ?array
    {
        $keys = [
            'Consultant 1er accueil',
            'Suivi actuel du client',
            'Partenaire Boutique',
            'Partenaire Like',
            'Opération',
            'Consultant Formateur',
            'Date de vente',
            'Montant HT',
            'PDF',
            'Date réunion de prépa',
            'Heures de formation obligatoires',
            'Heures de formation complémentaires',
            "Heures d'E-learning",
            "Nombre d'heures de formation",
            'Heures réalisées',
            'Heures restantes',
            '(F) Date de lancement',
            '(C) Date début de formation',
            '(C) Date de fin formation théorique',
            'Evaluation initiale',
            'Date certification',
            'Commande test / conversion compte ICDL',
            '(C) N° dossier EDOF',
            'Date envoi questionnaire à chaud',
            'Avis Google',
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