<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Str;

abstract class BaseClientImporter
{
    protected array $errors = [];
    protected int $created = 0;
    protected int $updated = 0;
    protected int $skipped = 0;

    /**
     * Retourne le nom du modèle (affiché dans l'UI)
     */
    abstract public static function getName(): string;

    /**
     * Retourne les colonnes requises pour identifier ce modèle
     */
    abstract public static function getRequiredColumns(): array;

    /**
     * Mappe une ligne Excel vers les champs Client
     */
    abstract protected function mapRow(array $row): array;

    /**
     * Vérifie si ce modèle correspond aux colonnes du fichier
     */
    public static function matches(array $fileColumns): bool
    {
        $fileColumns = array_map(fn($c) => mb_strtolower(trim((string) $c)), $fileColumns);
        foreach (static::getRequiredColumns() as $required) {
            if (! in_array(mb_strtolower(trim($required)), $fileColumns)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Importe un tableau de lignes (déjà parsées depuis Excel)
     */
    public function import(array $rows, string $sourceSheet = ''): array
    {
        foreach ($rows as $index => $row) {
            try {
                $data = $this->mapRow($row);

                if (empty($data['nom_tiers'])) {
                    $this->skipped++;
                    continue;
                }

                $data['source_sheet'] = $sourceSheet ?: static::getName();

                // Upsert sur ref_client si présente, sinon sur email
                $matchKey = ! empty($data['ref_client'])
                    ? ['ref_client' => $data['ref_client']]
                    : (! empty($data['email']) ? ['email' => $data['email']] : null);

                if ($matchKey) {
                    $client = Client::updateOrCreate($matchKey, $data);
                    $client->wasRecentlyCreated ? $this->created++ : $this->updated++;
                } else {
                    Client::create($data);
                    $this->created++;
                }
            } catch (\Throwable $e) {
                $this->errors[] = "Ligne " . ($index + 2) . " : " . $e->getMessage();
            }
        }

        return $this->getResult();
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

    // ─── Helpers ───────────────────────────────────────────────────────────────

    protected function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;

        // openpyxl / PhpSpreadsheet renvoie parfois un timestamp Excel (float)
        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (\Throwable) {}
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') return null;
        $clean = str_replace([' ', '€', ','], ['', '', '.'], (string) $value);
        return is_numeric($clean) ? (float) $clean : null;
    }

    protected function parseBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        return in_array(strtolower(trim((string) $value)), ['1', 'oui', 'yes', 'true', 'o', 'x']);
    }

    protected function mapEtat(mixed $value): ?string
    {
        $map = [
            'prospect'   => 'prospect',
            'en cours'   => 'en_cours',
            'à venir'    => 'prospect',
            'a venir'    => 'prospect',
            'terminé'    => 'termine',
            'termine'    => 'termine',
            'certifié'   => 'certifie',
            'certifie'   => 'certifie',
            'abandonné'  => 'abandonne',
            'abandonne'  => 'abandonne',
            'signée'     => 'en_cours',
            'signee'     => 'en_cours',
            'facturée'   => 'termine',
            'facturee'   => 'termine',
        ];

        $normalized = mb_strtolower(trim((string) $value));
        return $map[$normalized] ?? null;
    }
}