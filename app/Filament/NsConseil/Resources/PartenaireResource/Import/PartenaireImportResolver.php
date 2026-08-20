<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use Illuminate\Support\Facades\DB;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Charge le fichier Excel et importe une ou plusieurs feuilles.
 *
 * Importe les feuilles sélectionnées. Une sélection vide importe toutes les feuilles
 * compatibles ; les feuilles sans structure partenaire sont ignorées avec un diagnostic.
 */
class PartenaireImportResolver
{
    public const DEFAULT_TARGET_SHEET = 'MAJ';

    /**
     * Retourne les feuilles ayant la structure positionnelle minimale attendue.
     * Le tableau est directement utilisable par un Select Filament (clé => libellé).
     */
    public static function listImportableSheets(string $filePath): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);
        $result = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rows = $sheet->toArray(
                nullValue: null,
                calculateFormulas: false,
                formatData: false,
                returnCellRef: false
            );
            $headerCount = count(array_filter($rows[0] ?? [], fn ($value) => trim((string) $value) !== ''));
            if (count($rows) >= 2 && $headerCount >= PartenaireImporter::MIN_EXPECTED_COLUMNS) {
                $title = trim($sheet->getTitle());
                if ($title !== '') {
                    $result[$title] = $title;
                }
            }
        }

        return $result;
    }

    /**
     * @param  string  $filePath  Chemin absolu vers le .xlsx
     * @param  array  $defaults  Valeurs par défaut (entite_id, type, statut, conseiller_id…)
     * @param  string  $strategy  Stratégie d'importation (merge, overwrite, skip)
     * @param  array<string>|null  $targetSheets  Liste des onglets à importer (null = tous les onglets)
     * @return array{created:int, updated:int, skipped:int, errors:list<string>, sheets_processed:list<string>}
     */
    public static function importFile(string $filePath, array $defaults = [], string $strategy = 'merge', ?array $targetSheets = null): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        // ── Déterminer les onglets à importer ─────────────────────────────
        $autoSelectSheets = $targetSheets === null || $targetSheets === [];
        if ($autoSelectSheets) {
            $targetSheets = array_keys(self::listImportableSheets($filePath));
        }

        // ── Résultats globaux ─────────────────────────────────────────────
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $allErrors = [];
        $sheetsProcessed = [];

        // ── Traiter chaque onglet ────────────────────────────────────────
        return DB::transaction(function () use ($spreadsheet, $targetSheets, $defaults, $strategy) {
            $totalCreated = 0;
            $totalUpdated = 0;
            $totalSkipped = 0;
            $allErrors = [];
            $sheetsProcessed = [];

            foreach ($targetSheets as $targetSheet) {
            $worksheet = null;
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                if (mb_strtoupper(trim($sheet->getTitle())) === mb_strtoupper(trim($targetSheet))) {
                    $worksheet = $sheet;
                    break;
                }
            }

            if ($worksheet === null) {
                $allErrors[] = "Feuille '{$targetSheet}' introuvable dans le fichier.";
                continue;
            }

            // ── Lire les données brutes ───────────────────────────────────
            $rows = $worksheet->toArray(
                nullValue: null,
                calculateFormulas: true,
                formatData: false,
                returnCellRef: false
            );

            if (count($rows) < 2) {
                $allErrors[] = "La feuille '{$targetSheet}' est vide ou ne contient que l'en-tête.";
                continue;
            }

            // ── Vérifier la structure positionnelle avant d'écrire ───────────
            $nonEmptyHeaderCount = count(array_filter($rows[0] ?? [], fn ($value) => trim((string) $value) !== ''));
            if ($nonEmptyHeaderCount < PartenaireImporter::MIN_EXPECTED_COLUMNS) {
                if (! $autoSelectSheets) {
                    $allErrors[] = "[{$targetSheet}] Feuille ignorée : structure incompatible ({$nonEmptyHeaderCount} colonne(s) d'en-tête détectée(s)).";
                }
                continue;
            }

            // ── Déléguer à l'importer ─────────────────────────────────────
            $importer = new PartenaireImporter;
            $result = $importer->import($rows, $defaults, $strategy);

            $totalCreated += $result['created'];
            $totalUpdated += $result['updated'];
            $totalSkipped += $result['skipped'];
            $allErrors = array_merge($allErrors, $result['errors']);
            $sheetsProcessed[] = $targetSheet;
        }

            return [
                'created' => $totalCreated,
                'updated' => $totalUpdated,
                'skipped' => $totalSkipped,
                'errors' => $allErrors,
                'sheets_processed' => $sheetsProcessed,
            ];
        });
    }
}
