<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Charge le fichier Excel et importe une ou plusieurs feuilles.
 *
 * Par défaut, importe uniquement la feuille "MAJ" (source de vérité consolidée).
 * Les autres onglets (par conseiller, archives, COMM…) peuvent être importés
 * en spécifiant $targetSheets = null pour importer tous les onglets.
 */
class PartenaireImportResolver
{
    public const DEFAULT_TARGET_SHEET = 'MAJ';

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
        if ($targetSheets === null) {
            // Importer tous les onglets
            $targetSheets = [];
            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $targetSheets[] = $sheet->getTitle();
            }
        } elseif (empty($targetSheets)) {
            // Utiliser l'onglet par défaut si aucun spécifié
            $targetSheets = [self::DEFAULT_TARGET_SHEET];
        }

        // ── Résultats globaux ─────────────────────────────────────────────
        $totalCreated = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $allErrors = [];
        $sheetsProcessed = [];

        // ── Traiter chaque onglet ────────────────────────────────────────
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
    }
}
