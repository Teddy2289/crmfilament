<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Charge le fichier Excel et importe UNIQUEMENT la feuille "MAJ".
 *
 * La feuille MAJ est la source de vérité consolidée. Les autres onglets
 * (par conseiller, archives, COMM…) sont ignorés.
 */
class PartenaireImportResolver
{
    public const TARGET_SHEET = 'MAJ';

    /**
     * @param  string  $filePath  Chemin absolu vers le .xlsx
     * @param  array  $defaults  Valeurs par défaut (entite_id, type, statut, conseiller_id…)
     * @param  string  $strategy  Stratégie d'importation (merge, overwrite, skip)
     * @return array{created:int, updated:int, skipped:int, errors:list<string>}
     */
    public static function importFile(string $filePath, array $defaults = [], string $strategy = 'merge'): array
    {
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($filePath);

        // ── Chercher la feuille cible ─────────────────────────────────
        $worksheet = null;
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtoupper(trim($sheet->getTitle())) === mb_strtoupper(self::TARGET_SHEET)) {
                $worksheet = $sheet;
                break;
            }
        }

        if ($worksheet === null) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ["Feuille '".self::TARGET_SHEET."' introuvable dans le fichier."],
            ];
        }

        // ── Lire les données brutes ───────────────────────────────────
        $rows = $worksheet->toArray(
            nullValue: null,
            calculateFormulas: true,
            formatData: false,   // valeurs brutes : dates en serial, nombres en float
            returnCellRef: false
        );

        if (count($rows) < 2) {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'errors' => ["La feuille '".self::TARGET_SHEET."' est vide ou ne contient que l'en-tête."],
            ];
        }

        // ── Déléguer à l'importer ─────────────────────────────────────
        $importer = new PartenaireImporter;

        return $importer->import($rows, $defaults, $strategy);
    }
}
