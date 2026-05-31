<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PartenaireImportResolver
{
    /**
     * Charge le fichier Excel et importe chaque feuille éligible.
     *
     * @param  string  $filePath  Chemin absolu vers le .xlsx
     * @param  array   $defaults  Valeurs par défaut appliquées à toutes les lignes
     * @return array<string, array{created:int, updated:int, skipped:int, errors:list<string>}>
     */
    public static function importFile(string $filePath, array $defaults = []): array
    {
        $results = [];

        $spreadsheet = IOFactory::load($filePath);

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheetName = $worksheet->getTitle();

            /*
             * toArray(null, true, true, true) :
             *   - arg1 null     → valeur brute (pas de conversion)
             *   - arg2 true     → calculer les formules
             *   - arg3 true     → formater les valeurs
             *   - arg4 true     → retourner les indices de colonnes réels (A=0…)
             *
             * On préfère toArray() simple pour avoir des indices entiers 0-based
             * compatibles avec le mapping du PartenaireImporter.
             */
            $rows = $worksheet->toArray(
                nullValue: null,
                calculateFormulas: true,
                formatData: false,   // false = valeurs brutes (dates en serial, nombres en float)
                returnCellRef: false
            );

            // ── Feuille vide ──────────────────────────────────────────
            if (empty($rows) || count($rows) < 2) {
                $results[$sheetName] = self::emptyResult(
                    "Feuille '{$sheetName}' ignorée (vide ou moins de 2 lignes)"
                );
                continue;
            }

            // ── Vérifier la présence de "Raison sociale" ──────────────
            if (!self::sheetHasRaisonSociale($rows)) {
                $results[$sheetName] = self::emptyResult(
                    "Feuille '{$sheetName}' ignorée : aucune colonne 'Raison sociale' trouvée"
                );
                continue;
            }

            // ── Import ────────────────────────────────────────────────
            $importer = new PartenaireImporter();
            $result = $importer->import($rows, $sheetName, $defaults);
            $results[$sheetName] = $result;
        }

        if (empty($results)) {
            $results['_global'] = self::emptyResult(
                'Aucune feuille éligible trouvée dans le fichier.'
            );
        }

        return $results;
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Vérifie si au moins une cellule du tableau contient « Raison sociale ».
     */
    protected static function sheetHasRaisonSociale(array $rows): bool
    {
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }
            foreach ($row as $cell) {
                if (is_string($cell) && stripos($cell, 'Raison sociale') !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Résultat vide avec un message d'erreur/info.
     */
    protected static function emptyResult(string $message): array
    {
        return [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [$message],
        ];
    }
}