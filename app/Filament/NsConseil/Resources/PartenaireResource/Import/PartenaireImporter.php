<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Partenaire;

class PartenaireImporter
{
    protected array $errors = [];
    protected int $created  = 0;
    protected int $updated  = 0;
    protected int $skipped  = 0;

    public static function getRequiredColumns(): array
    {
        return ['Raison sociale', 'Siret', 'CP', 'Ville'];
    }

    public static function matches(array $fileColumns): bool
    {
        $normalized = array_map(fn($c) => mb_strtolower(trim((string) $c)), $fileColumns);
        foreach (static::getRequiredColumns() as $required) {
            if (! in_array(mb_strtolower(trim($required)), $normalized)) {
                return false;
            }
        }
        return true;
    }

    public function import(array $rows, string $sourceSheet = '', array $defaults = []): array
    {
        foreach ($rows as $index => $row) {
            try {
                $data = $this->mapRow($row, $defaults);

                if (empty($data['nom'])) {
                    $this->skipped++;
                    continue;
                }

                $siret = $data['siret'] ?? null;

                if ($siret) {
                    $partenaire = Partenaire::updateOrCreate(['siret' => $siret], $data);
                } else {
                    $partenaire = Partenaire::updateOrCreate(
                        ['nom' => $data['nom'], 'ville' => $data['ville'] ?? null],
                        $data
                    );
                }

                $partenaire->wasRecentlyCreated ? $this->created++ : $this->updated++;

            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($index + 2) . " : " . $e->getMessage();
            }
        }

        return $this->getResult();
    }

    protected function mapRow(array $row, array $defaults = []): array
    {
        $siret       = $this->cleanSiret($row['Siret'] ?? null);
        $cp          = $this->cleanCodePostal($row['CP'] ?? null);
        $departement = $cp ? substr($cp, 0, 2) : null;

        return array_filter([
            'nom'                  => trim((string) ($row['Raison sociale'] ?? '')),
            'siret'                => $siret,
            'adresse'              => trim((string) ($row['Adresse'] ?? '')),
            'code_postal'          => $cp,
            'ville'                => trim((string) ($row['Ville'] ?? '')),
            'departement'          => $departement,
            'telephone'            => $this->cleanTelephone($row['telephone 1'] ?? null),
            'nb_salaries'          => $this->parseNbSalaries($row['nbr salariés'] ?? null),
            'chiffre_affaires'     => $this->parseCA($row["Chiffred'affaires"] ?? $row["Chiffre d'affaires"] ?? null),
            'type'                 => $defaults['type']                ?? OrganizationType::EntrepriseDirecte->value,
            'statut'               => $defaults['statut']              ?? OrganizationStatus::AProspecter->value,
            'commercial_id'        => $defaults['commercial_id']       ?? null,
            'nomenclature_interne' => $defaults['nomenclature_interne'] ?? 'ENT_DIRECTE',
            'secteur_activite'     => $defaults['secteur_activite']    ?? 'Non renseigné',
        ], fn($v) => $v !== null && $v !== '');
    }

    // ─── Helpers ────────────────────────────────────────────────────

    protected function cleanSiret(mixed $value): ?string
    {
        if (empty($value)) return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        return strlen($digits) >= 9 ? $digits : null;
    }

    protected function cleanCodePostal(mixed $value): ?string
    {
        if (empty($value)) return null;
        $digits = preg_replace('/\D/', '', (string) $value);
        return $digits !== '' ? str_pad($digits, 5, '0', STR_PAD_LEFT) : null;
    }

    protected function cleanTelephone(mixed $value): ?string
    {
        if (empty($value)) return null;
        $clean = trim((string) $value);
        return $clean !== '' ? $clean : null;
    }

    protected function parseNbSalaries(mixed $value): ?int
    {
        if ($value === null || $value === '') return null;
        // Supprimer tous les caractères non-chiffres (espaces normaux, insécables, etc.)
        $str   = (string) $value;
        $str   = str_replace([' ', "\xc2\xa0", "\xe2\x80\xaf", "\xe2\x80\x8f", '.', ','], '', $str);
        $clean = preg_replace('/[^0-9]/', '', $str);
        return $clean !== '' ? (int) $clean : null;
    }

    protected function parseCA(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        $str = (string) $value;
        // Supprimer le symbole €, espaces normaux et insécables, points de milliers
        $str = str_replace(['€', ' ', "\xc2\xa0", "\xe2\x80\xaf", "\xe2\x80\x8f", '.'], '', $str);
        // Remplacer la virgule décimale par un point
        $str = str_replace(',', '.', $str);
        // Ne garder que chiffres et point
        $clean = preg_replace('/[^0-9.]/', '', $str);
        return is_numeric($clean) ? (float) $clean : null;
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