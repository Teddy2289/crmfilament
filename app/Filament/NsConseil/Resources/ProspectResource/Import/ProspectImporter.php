<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Import;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Models\Prospect;
use App\Models\User;

class ProspectImporter
{
    protected array $errors  = [];
    protected int   $created = 0;
    protected int   $updated = 0;
    protected int   $skipped = 0;

    protected array $columnMapping = [];
    protected array $defaults      = [];

    public static function getRequiredColumns(): array
    {
        return ['nom', 'telephone'];
    }

    // ── Point d'entrée ────────────────────────────────────────────────
    public function import(array $rows, string $sourceSheet = '', array $defaults = []): array
    {
        $this->defaults = $defaults;

        $headerRowIndex = $this->findHeaderRow($rows);

        if ($headerRowIndex === null) {
            $this->errors[] = "Impossible de trouver la ligne d'en-tête (colonne 'Nom' ou 'Raison sociale' attendue)";
            return $this->getResult();
        }

        $this->buildColumnMapping($rows[$headerRowIndex]);

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $data = $this->mapRow($row);

                if (empty($data['nom'])) {
                    $this->skipped++;
                    $this->errors[] = "Ligne " . ($i + 1) . " : Nom manquant — ignorée";
                    continue;
                }

                // Déduplication : téléphone principal OU (nom + département)
                $telephone = $data['telephone'] ?? null;
                if ($telephone) {
                    $prospect = Prospect::updateOrCreate(
                        ['telephone' => $telephone],
                        $data
                    );
                } else {
                    $prospect = Prospect::updateOrCreate(
                        ['nom' => $data['nom'], 'departement' => $data['departement'] ?? null],
                        $data
                    );
                }

                $prospect->wasRecentlyCreated ? $this->created++ : $this->updated++;

            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($i + 1) . " : " . $e->getMessage();
            }
        }

        return $this->getResult();
    }

    // ── Détection en-tête ─────────────────────────────────────────────
    protected function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (empty($row)) continue;
            foreach ($row as $cell) {
                $cell = mb_strtolower(trim((string) $cell));
                if (
                    str_contains($cell, 'nom') ||
                    str_contains($cell, 'raison sociale') ||
                    str_contains($cell, 'entreprise') ||
                    str_contains($cell, 'telephone') ||
                    str_contains($cell, 'téléphone')
                ) {
                    return $index;
                }
            }
        }
        return null;
    }

    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') return false;
        }
        return true;
    }

    // ── Mapping colonnes ──────────────────────────────────────────────
    protected function buildColumnMapping(array $headerRow): void
    {
        $fieldAliases = [
            'nom' => [
                'nom', 'raison sociale', 'raison_sociale', 'entreprise',
                'organisation', 'entite', 'entité'
            ],
            'type_pressenti' => [
                'type', 'type pressenti', 'type_pressenti', 'categorie', 'catégorie'
            ],
            'siret' => ['siret', 'n° siret', 'numero siret'],
            'departement' => ['dpt', 'departement', 'département', 'dep'],
            'code_postal' => ['cp', 'code postal', 'code_postal'],
            'ville' => ['ville', 'commune'],
            'adresse' => ['adresse', 'address'],
            'secteur_activite' => [
                "secteur d'activité", "secteur d'activites", 'secteur_activite', 'secteur'
            ],
            'nb_salaries' => [
                'nbrs de salariés', 'nb salariés', 'nb_salaries', 'salariés', 'effectif',
                'nbr de salariés'
            ],
            'chiffre_affaires' => ['ca', "chiffre d'affaires", 'chiffre_affaires'],
            'telephone' => [
                'téléphone', 'telephone', 'tel', 'tél', 'téléphone 1', 'telephone 1', 'tel 1'
            ],
            'telephone_alt' => ['téléphone 2', 'telephone 2', 'tel 2', 'tél 2'],
            'email' => ['email', 'mail', 'e-mail'],
            'interlocuteur_nom' => [
                'interlocuteur', 'contact', 'interlocuteur nom', 'nom contact'
            ],
            'interlocuteur_fonction' => ['fonction', 'poste', 'titre'],
            'interlocuteur_telephone' => ['tel interlocuteur', 'tél interlocuteur'],
            'interlocuteur_email' => ['email interlocuteur', 'mail interlocuteur'],
            'statut' => ['statut', 'etat', 'état', 'situation'],
            'teleprospecteur' => ['conseiller', 'téléprospecteur', 'teleprospecteur', 'agent'],
            'rappel_planifie_at' => [
                'rappel', 'date rappel', 'rappel planifié', 'date de rappel',
                'date de 1er contact', 'date_contact', 'date'
            ],
            'description' => [
                'commentaire', 'commentaires', 'notes', 'description',
                'commentaires/situation actuelle', 'situation actuelle'
            ],
        ];

        $normalizedHeader = array_map(
            fn($col) => mb_strtolower(trim((string) $col)),
            $headerRow
        );

        foreach ($fieldAliases as $field => $aliases) {
            foreach ($normalizedHeader as $colIndex => $colName) {
                foreach ($aliases as $alias) {
                    if ($colName === $alias || str_contains($colName, $alias)) {
                        $this->columnMapping[$field] = $colIndex;
                        break 2;
                    }
                }
            }
        }
    }

    // ── Mapping d'une ligne ────────────────────────────────────────────
    protected function mapRow(array $row): array
    {
        $get = function (string $field) use ($row): mixed {
            $index = $this->columnMapping[$field] ?? null;
            if ($index === null || !array_key_exists($index, $row)) return null;
            $value = $row[$index];
            return is_string($value) ? trim($value) : $value;
        };

        $cp          = $this->cleanCodePostal($get('code_postal'));
        $departement = $this->extractDepartement($get('departement'))
            ?? ($cp ? substr($cp, 0, 2) : null);

        $statut = $this->resolveStatut($get('statut'))
            ?? ($this->defaults['statut'] ?? ProspectStatut::AC->value);

        $teleprospecteurId = $this->resolveUserId($get('teleprospecteur'));

        $data = [
            'nom'                      => $get('nom'),
            'type_pressenti'           => $this->resolveType($get('type_pressenti')),
            'siret'                    => $this->cleanSiret($get('siret')),
            'departement'              => $departement,
            'code_postal'              => $cp,
            'ville'                    => $get('ville'),
            'adresse'                  => $get('adresse'),
            'secteur_activite'         => $get('secteur_activite') ?: ($this->defaults['secteur_activite'] ?? null),
            'nb_salaries'              => $this->cleanInt($get('nb_salaries')),
            'chiffre_affaires'         => $this->cleanDecimal($get('chiffre_affaires')),
            'telephone'                => $this->cleanTelephone($get('telephone')),
            'telephone_alt'            => $this->cleanTelephone($get('telephone_alt')),
            'email'                    => $get('email'),
            'interlocuteur_nom'        => $get('interlocuteur_nom'),
            'interlocuteur_fonction'   => $get('interlocuteur_fonction'),
            'interlocuteur_telephone'  => $this->cleanTelephone($get('interlocuteur_telephone')),
            'interlocuteur_email'      => $get('interlocuteur_email'),
            'statut'                   => $statut,
            'teleprospecteur_id'       => $teleprospecteurId ?? ($this->defaults['teleprospecteur_id'] ?? null),
            'rappel_planifie_at'       => $this->parseDate($get('rappel_planifie_at')),
            'description'              => $get('description'),
        ];

        return array_filter($data, fn($v) => $v !== null && $v !== '');
    }

    // ── Resolvers ────────────────────────────────────────────────────
    protected function resolveStatut(?string $value): ?string
    {
        if (empty($value)) return null;

        $map = [
            'ac'          => ProspectStatut::AC->value,
            'à contacter' => ProspectStatut::AC->value,
            'a contacter' => ProspectStatut::AC->value,
            'nr'          => ProspectStatut::STD_NR->value,
            'non répondu' => ProspectStatut::STD_NR->value,
            'ko'          => ProspectStatut::KO->value,
            'refus'       => ProspectStatut::KO->value,
            'qf'          => ProspectStatut::QF->value,
            'qualifié'    => ProspectStatut::QF->value,
        ];

        $lower = mb_strtolower(trim($value));
        return $map[$lower] ?? null;
    }

    protected function resolveType(?string $value): ?string
    {
        if (empty($value)) return null;
        $lower = mb_strtolower(trim($value));

        foreach (OrganizationType::cases() as $case) {
            if (str_contains($lower, mb_strtolower($case->value))) {
                return $case->value;
            }
        }
        return null;
    }

    protected function resolveUserId(?string $name): ?int
    {
        if (empty($name)) return null;

        $name  = trim((string) $name);
        $parts = preg_split('/\s+/', $name);

        $query = User::query()->whereIn(
            'role_cache',
            ['teleprospecteur', 'commercial', 'team_leader', 'administrateur']
        );

        if (count($parts) >= 2) {
            $query->where(function ($q) use ($parts) {
                $q->where(function ($q2) use ($parts) {
                    $q2->where('prenom', 'like', "%{$parts[0]}%")
                        ->where('nom', 'like', "%{$parts[1]}%");
                })->orWhere(function ($q2) use ($parts) {
                    $q2->where('nom', 'like', "%{$parts[0]}%")
                        ->where('prenom', 'like', "%{$parts[1]}%");
                });
            });
        } else {
            $query->where(function ($q) use ($name) {
                $q->where('nom', 'like', "%{$name}%")
                    ->orWhere('prenom', 'like', "%{$name}%");
            });
        }

        return $query->value('id');
    }

    protected function extractDepartement(?string $value): ?string
    {
        if (empty($value)) return null;
        if (preg_match('/^\d{2,3}$/', trim($value))) return trim($value);
        return null;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;
        try {
            if ($value instanceof \DateTime) return $value->format('Y-m-d H:i:s');
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d H:i:s');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    protected function cleanCodePostal(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        return $digits !== '' ? str_pad($digits, 5, '0', STR_PAD_LEFT) : null;
    }

    protected function cleanTelephone(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $clean = preg_replace('/\s+/', ' ', trim((string) $value));
        return $clean !== '' ? $clean : null;
    }

    protected function cleanSiret(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        if (strlen($digits) === 14 || strlen($digits) === 9) return $digits;
        return $digits !== '' ? str_pad($digits, 14, '0', STR_PAD_LEFT) : null;
    }

    protected function cleanInt(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        $digits = preg_replace('/[^\d]/', '', (string) $value);
        return $digits !== '' ? (int) $digits : null;
    }

    protected function cleanDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        $clean = preg_replace('/\s/', '', (string) $value);
        $clean = str_replace(',', '.', $clean);
        $clean = preg_replace('/[^\d.]/', '', $clean);
        return is_numeric($clean) ? $clean : null;
    }

    protected function getResult(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors'  => $this->errors,
        ];
    }
}
