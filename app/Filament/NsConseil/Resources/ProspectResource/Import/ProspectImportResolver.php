<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ProspectImportResolver
{
    /**
     * Charge le fichier Excel et importe chaque feuille éligible.
     */
    public static function importFile(string $filePath, array $defaults = []): array
    {
        $results = [];
        $spreadsheet = IOFactory::load($filePath);

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheetName = $worksheet->getTitle();

            $rows = $worksheet->toArray(
                nullValue: null,
                calculateFormulas: true,
                formatData: false,
                returnCellRef: false
            );

            if (empty($rows) || count($rows) < 2) {
                $results[$sheetName] = self::emptyResult(
                    "Feuille '{$sheetName}' ignorée (vide ou moins de 2 lignes)"
                );

                continue;
            }

            if (! self::sheetHasProspectHeader($rows)) {
                $results[$sheetName] = self::emptyResult(
                    "Feuille '{$sheetName}' ignorée : aucune colonne reconnue (Nom, Téléphone…)"
                );

                continue;
            }

            $importer = new ProspectImporter;
            $results[$sheetName] = $importer->import($rows, $sheetName, $defaults);
        }

        if (empty($results)) {
            $results['_global'] = self::emptyResult('Aucune feuille éligible trouvée.');
        }

        return $results;
    }

    protected static function sheetHasProspectHeader(array $rows): bool
    {
        $keywords = ['nom', 'telephone', 'téléphone', 'raison sociale', 'entreprise'];
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }
            foreach ($row as $cell) {
                if (! is_string($cell)) {
                    continue;
                }
                $lower = mb_strtolower(trim($cell));
                foreach ($keywords as $kw) {
                    if (str_contains($lower, $kw)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected static function emptyResult(string $message): array
    {
        return ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => [$message]];
    }
}
