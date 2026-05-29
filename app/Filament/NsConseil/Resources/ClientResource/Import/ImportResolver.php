<?php

namespace App\Filament\NsConseil\Resources\ClientResource\Import;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportResolver
{
    /** @var class-string<BaseClientImporter>[] */
    protected static array $importers = [
        CrmLikeImporter::class,
        CrmAopiaAboImporter::class,
        Crm01FcImporter::class,
    ];

    public static function getOptions(): array
    {
        $options = [];
        foreach (static::$importers as $class) {
            $options[$class] = $class::getName();
        }
        return $options;
    }

    /** @return class-string<BaseClientImporter>|null */
    public static function detectFromColumns(array $columns): ?string
    {
        foreach (static::$importers as $class) {
            if ($class::matches($columns)) {
                return $class;
            }
        }
        return null;
    }

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

            if (empty($data)) {
                continue;
            }

            // Trouver la ligne d'en-tête : première ligne dont la première cellule
            // est une chaîne non-numérique (ignore les lignes de comptage comme "7719")
            $headerRowIndex = null;
            foreach ($data as $idx => $row) {
                $firstCell = trim((string) ($row[0] ?? ''));
                if ($firstCell !== '' && ! is_numeric($firstCell)) {
                    $headerRowIndex = $idx;
                    break;
                }
            }

            // Si toutes les premières cellules sont numériques, prendre la ligne 0 par défaut
            if ($headerRowIndex === null) {
                $headerRowIndex = 0;
            }

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

    /** @param class-string<BaseClientImporter>|null $importerClass */
    public static function importFile(string $path, ?string $importerClass = null): array
    {
        $sheets = static::parseFile($path);
        $results = [];

        foreach ($sheets as $sheetName => $sheetData) {
            $class = $importerClass ?? static::detectFromColumns($sheetData['headers']);

            if (! $class) {
                $sample = implode(', ', array_slice(array_filter($sheetData['headers']), 0, 8));
                $results[$sheetName] = [
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors'  => ["Modèle non reconnu pour « {$sheetName} ». Colonnes : {$sample}…"],
                ];
                continue;
            }

            /** @var BaseClientImporter $importer */
            $importer = new $class();
            $results[$sheetName] = $importer->import($sheetData['rows'], $sheetName);
            $results[$sheetName]['model'] = $class::getName();
        }

        return $results;
    }
}