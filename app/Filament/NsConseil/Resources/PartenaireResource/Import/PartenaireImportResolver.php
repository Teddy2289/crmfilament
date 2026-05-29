<?php

namespace App\Filament\NsConseil\Resources\PartenaireResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class PartenaireImportResolver
{
    public static function parseFile(string $path): array
    {
        $path = str_replace('\\', '/', $path);

        if (! file_exists($path) || ! is_readable($path)) {
            throw new \RuntimeException("Fichier introuvable ou illisible : {$path}");
        }

        $spreadsheet = IOFactory::load($path);
        $result = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheetName = $sheet->getTitle();
            $data = $sheet->toArray(null, true, true, false);

            if (empty($data)) continue;

            // Trouver la ligne d'en-tête (première ligne dont la cellule A n'est pas numérique)
            $headerRowIndex = null;
            foreach ($data as $idx => $row) {
                $first = trim((string) ($row[0] ?? ''));
                if ($first !== '' && ! is_numeric($first)) {
                    $headerRowIndex = $idx;
                    break;
                }
            }

            if ($headerRowIndex === null) continue;

            $headers = array_map('trim', array_map('strval', $data[$headerRowIndex]));

            $rows = [];
            for ($i = $headerRowIndex + 1; $i < count($data); $i++) {
                $rawRow = $data[$i];
                if (empty(array_filter($rawRow, fn($v) => $v !== null && $v !== ''))) {
                    continue;
                }
                $row = [];
                foreach ($headers as $colIdx => $header) {
                    if ($header === '') continue;
                    $row[$header] = $rawRow[$colIdx] ?? null;
                }
                $rows[] = $row;
            }

            if (! empty($rows)) {
                $result[$sheetName] = [
                    'headers' => $headers,
                    'rows'    => $rows,
                ];
            }
        }

        return $result;
    }

    public static function importFile(string $path, array $defaults = []): array
    {
        $sheets  = static::parseFile($path);
        $results = [];

        foreach ($sheets as $sheetName => $sheetData) {
            if (! PartenaireImporter::matches($sheetData['headers'])) {
                $sample = implode(', ', array_slice(array_filter($sheetData['headers']), 0, 6));
                $results[$sheetName] = [
                    'created' => 0, 'updated' => 0, 'skipped' => 0,
                    'errors'  => ["Feuille « {$sheetName} » ignorée — colonnes inconnues : {$sample}…"],
                ];
                continue;
            }

            $importer = new PartenaireImporter();
            $results[$sheetName] = $importer->import($sheetData['rows'], $sheetName, $defaults);
        }

        return $results;
    }
}