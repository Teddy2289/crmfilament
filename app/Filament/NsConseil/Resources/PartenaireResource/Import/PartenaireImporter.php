<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Partenaire;
use App\Models\User;

class PartenaireImporter
{
    protected array $errors = [];
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;

    protected array $columnMapping = [];
    protected array $defaults = [];

    // ─── Colonnes minimales requises ────────────────────────────────
    public static function getRequiredColumns(): array
    {
        return ['raison sociale', 'cp', 'ville'];
    }

    // ─── Point d'entrée principal ────────────────────────────────────
    public function import(array $rows, string $sourceSheet = '', array $defaults = []): array
    {
        $this->defaults = $defaults;

        $headerRowIndex = $this->findHeaderRow($rows);

        if ($headerRowIndex === null) {
            $this->errors[] = "Impossible de trouver la ligne d'en-tête contenant 'Raison sociale'";
            return $this->getResult();
        }

        $this->buildColumnMapping($rows[$headerRowIndex]);

        // Vérifier les colonnes obligatoires
        $missing = [];
        foreach (self::getRequiredColumns() as $col) {
            // On cherche si l'une des clés du mapping couvre ce champ
            $found = match ($col) {
                'raison sociale' => isset($this->columnMapping['raison_sociale']),
                'cp' => isset($this->columnMapping['cp']),
                'ville' => isset($this->columnMapping['ville']),
                default => true,
            };
            if (!$found) {
                $missing[] = $col;
            }
        }

        if (!empty($missing)) {
            $this->errors[] = "Colonnes obligatoires introuvables dans l'en-tête : " . implode(', ', $missing);
            return $this->getResult();
        }

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                $data = $this->mapRow($row, $i + 1);

                if (empty($data['nom'])) {
                    $this->skipped++;
                    $this->errors[] = "Ligne " . ($i + 1) . " : Raison sociale manquante — ligne ignorée";
                    continue;
                }

                $siret = $data['siret'] ?? null;

                if ($siret) {
                    $partenaire = Partenaire::updateOrCreate(
                        ['siret' => $siret],
                        $data
                    );
                } else {
                    $partenaire = Partenaire::updateOrCreate(
                        ['nom' => $data['nom'], 'ville' => $data['ville'] ?? null],
                        $data
                    );
                }

                $partenaire->wasRecentlyCreated ? $this->created++ : $this->updated++;

            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($i + 1) . " : " . $e->getMessage();
            }
        }

        return $this->getResult();
    }

    // ─── Détection de la ligne d'en-tête ────────────────────────────
    protected function findHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            if (empty($row)) {
                continue;
            }
            foreach ($row as $cell) {
                $cell = mb_strtolower(trim((string) $cell));
                if (str_contains($cell, 'raison sociale') || str_contains($cell, 'raison_sociale')) {
                    return $index;
                }
            }
        }
        return null;
    }

    // ─── Ligne vide ──────────────────────────────────────────────────
    protected function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim((string) $cell) !== '') {
                return false;
            }
        }
        return true;
    }

    // ─── Construction du mapping colonne → index ─────────────────────
    /**
     * Correspondances entre les noms de champs internes et les variantes
     * acceptées dans l'en-tête Excel (comparaison insensible à la casse,
     * après normalisation unicode basique).
     *
     * Structure réelle du fichier :
     *   0  Conseiller
     *   1  Dpt
     *   2  Etat
     *   3  Date de 1er contact
     *   4  Commentaires/situation actuelle
     *   5  Raison sociale
     *   6  Adresse
     *   7  CP
     *   8  Ville
     *   9  Téléphone 1
     *  10  Téléphone 2
     *  11  Nbrs de salariés
     *  12  Secteur d'activités
     *  13  CA
     *  14  Siret
     */
    protected function buildColumnMapping(array $headerRow): void
    {
        $fieldAliases = [
            'conseiller' => ['conseiller'],
            'dpt' => ['dpt', 'departement', 'département'],
            'etat' => ['etat', 'état', 'statut'],
            'date_contact' => ['date de 1er contact', 'date_contact', 'date de l\'évaluation', 'date_evaluation', 'date'],
            'commentaire' => ['commentaire', 'commentaires', 'commentaires/situation actuelle', 'situation actuelle'],
            'raison_sociale' => ['raison sociale', 'raison_sociale', 'nom', 'entreprise'],
            'adresse' => ['adresse', 'address'],
            'cp' => ['cp', 'code postal', 'code_postal'],
            'ville' => ['ville', 'commune', 'city'],
            'telephone' => ['téléphone 1', 'telephone 1', 'tel 1', 'téléphone', 'telephone', 'tel', 'phone'],
            'telephone2' => ['téléphone 2', 'telephone 2', 'tel 2'],
            'nb_salaries' => ['nbrs de salariés', 'nbr de salariés', 'nb salariés', 'nb_salaries', 'nombre salariés', 'salariés', 'effectif'],
            'secteur_activite' => ['secteur d\'activités', 'secteur d\'activité', 'secteur_activite', 'secteur'],
            'chiffre_affaires' => ['ca', 'chiffre d\'affaires', 'chiffre_affaires', 'ca (€)'],
            'siret' => ['siret', 'n° siret', 'numero siret'],
        ];

        $normalizedHeader = array_map(
            fn($col) => $this->normalizeString((string) $col),
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

    // ─── Transformation d'une ligne en tableau de données ────────────
    protected function mapRow(array $row, int $lineNumber): array
    {
        $get = function (string $field) use ($row): mixed {
            $index = $this->columnMapping[$field] ?? null;
            if ($index === null || !array_key_exists($index, $row)) {
                return null;
            }
            $value = $row[$index];
            // Convertir les entiers/floats Excel en string quand nécessaire
            return is_string($value) ? trim($value) : $value;
        };

        // Code postal ─────────────────────────────────────────────────
        $cp = $this->cleanCodePostal($get('cp'));

        // Département (depuis colonne dédiée ou déduit du CP) ─────────
        $departement = $this->extractDepartement($get('dpt'))
            ?? ($cp ? substr($cp, 0, 2) : null);

        // Commercial (par nom dans la colonne "Conseiller") ────────────
        $commercialId = $this->resolveCommercialId($get('conseiller'));

        // Secteur d'activité (colonne ou défaut) ──────────────────────
        $secteur = $get('secteur_activite') ?: ($this->defaults['secteur_activite'] ?? 'Non renseigné');

        // SIRET ───────────────────────────────────────────────────────
        $siret = $this->cleanSiret($get('siret'));

        // Construction du tableau ─────────────────────────────────────
        $data = [
            'nom' => $get('raison_sociale'),
            'siret' => $siret,
            'adresse' => $get('adresse'),
            'code_postal' => $cp,
            'ville' => $get('ville'),
            'departement' => $departement,
            'telephone' => $this->cleanTelephone($get('telephone')),
            'telephone2' => $this->cleanTelephone($get('telephone2')),   // si la colonne existe dans la BDD
            'nb_salaries' => $this->cleanInt($get('nb_salaries')),
            'chiffre_affaires' => $this->cleanDecimal($get('chiffre_affaires')),
            'secteur_activite' => $secteur,
            'commentaire_import' => $get('commentaire'),
            'date_evaluation' => $this->parseDate($get('date_contact')),
            'statut_prospection' => $get('etat'),
            // Valeurs par défaut (remplacées si le fichier contient mieux)
            'type' => $this->defaults['type'] ?? OrganizationType::EntrepriseDirecte->value,
            'statut' => $this->defaults['statut'] ?? OrganizationStatus::AProspecter->value,
            'commercial_id' => $commercialId ?? $this->defaults['commercial_id'] ?? null,
            'nomenclature_interne' => $this->defaults['nomenclature_interne'] ?? 'ENT_DIRECTE',
        ];

        // Supprimer les nulls et chaînes vides pour ne pas écraser de données existantes
        return array_filter($data, fn($v) => $v !== null && $v !== '');
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    protected function normalizeString(string $value): string
    {
        $value = mb_strtolower(trim($value));
        // Normalisation basique des accents pour la comparaison
        $from = ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'û', 'ù', 'ç', 'œ'];
        $to = ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'u', 'u', 'c', 'oe'];
        // On compare sans normaliser pour conserver les accents dans le mapping affiché,
        // mais on les conserve dans les aliases → la comparaison directe suffit.
        return $value;
    }

    protected function resolveCommercialId(mixed $name): ?int
    {
        if (empty($name)) {
            return null;
        }

        $name = trim((string) $name);
        $parts = preg_split('/\s+/', $name);

        $query = User::query()->whereIn('role_cache', ['commercial', 'team_leader', 'administrateur']);

        if (count($parts) >= 2) {
            // Prénom NOM ou NOM Prénom → tester les deux ordres
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

    protected function extractDepartement(?string $dptString): ?string
    {
        if (empty($dptString)) {
            return null;
        }

        // Déjà un code numérique (ex : "44")
        if (preg_match('/^\d{2,3}$/', trim($dptString))) {
            return trim($dptString);
        }

        $departements = [
            'loire-atlantique' => '44',
            'vendee' => '85',
            'vendée' => '85',
            'ille-et-vilaine' => '35',
            'morbihan' => '56',
            'cotes-d\'armor' => '22',
            'maine-et-loire' => '49',
            'sarthe' => '72',
            'mayenne' => '53',
            'loire-atlantique' => '44',
        ];

        $lower = mb_strtolower($dptString);
        foreach ($departements as $key => $code) {
            if (str_contains($lower, $key)) {
                return $code;
            }
        }

        return null;
    }

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }
            // Valeur numérique Excel (serial date)
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                    ->format('Y-m-d');
            }
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }

    protected function cleanCodePostal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        return $digits !== '' ? str_pad($digits, 5, '0', STR_PAD_LEFT) : null;
    }

    protected function cleanTelephone(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $clean = preg_replace('/\s+/', ' ', trim((string) $value));
        return $clean !== '' ? $clean : null;
    }

    protected function cleanSiret(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', (string) $value);
        // SIRET = 14 chiffres ; on accepte aussi SIREN (9)
        if (strlen($digits) === 14 || strlen($digits) === 9) {
            return $digits;
        }
        // Tentative de padding si proche
        if (strlen($digits) > 0) {
            return str_pad($digits, 14, '0', STR_PAD_LEFT);
        }
        return null;
    }

    protected function cleanInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $digits = preg_replace('/[^\d]/', '', (string) $value);
        return $digits !== '' ? (int) $digits : null;
    }

    protected function cleanDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Supprimer espaces et séparateurs de milliers, normaliser virgule → point
        $clean = preg_replace('/\s/', '', (string) $value);
        $clean = str_replace([' ', "\u{00A0}", "\u{202F}"], '', $clean); // espaces insécables
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
            'errors' => $this->errors,
        ];
    }
}