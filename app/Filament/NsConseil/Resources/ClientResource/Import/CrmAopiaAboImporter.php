<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

class CrmAopiaAboImporter extends BaseClientImporter
{
    public static function getName(): string
    {
        return 'CRM AOPIA-ABO';
    }

    public static function getRequiredColumns(): array
    {
        return ['Réf. client', 'Tiers', 'Interlocuteur', 'Montant cpf'];
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
            'adresse'           => trim((string) ($row['Adresse'] ?? '')),
            'entreprise'        => trim((string) ($row['Entreprise'] ?? '')),
            'date_naissance'    => $this->parseDate($row['Date de naissance'] ?? null),
            'montant_cpf'       => $this->parseFloat($row['Montant cpf'] ?? null),
            'ne_plus_contacter' => $this->parseBool($row['Ne plus contacter'] ?? false),
            'etat'              => $this->mapEtat($row['État'] ?? ''),
            'extra_data'        => $this->buildExtra($row),
        ], fn($v) => $v !== null && $v !== '');
    }

    private function buildExtra(array $row): ?array
    {
        $keys = [
            'Suivi actuel du Client',
            'Interlocuteur',
            'Date de vente',
            'Montant HT',
            'Consultant Formateur',
            'Date de lancement',
            'PDF',
            '(C) Date début de formation',
            '(C) Date de fin formation théorique',
            'Evaluation initiale',
            'Date certification',
            '(C) N° dossier EDOF',
            'Heures de formation obligatoires',
            'Heures de formation complémentaires',
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