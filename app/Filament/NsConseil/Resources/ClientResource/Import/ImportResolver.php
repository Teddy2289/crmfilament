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
            // formatData=false : récupère les valeurs numériques brutes (ex. 5000)
            // plutôt que les chaînes formatées selon l'affichage Excel (ex. "5 000 €"
            // ou "5.000" selon la locale), qui faussaient le parsing des montants
            // (division silencieuse par 1000 dans parseFloat()).
            $data = $sheet->toArray(null, true, false, false);

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

            // Dédoublonner les en-têtes identiques (ex. "Ville" et "Code postal"
            // apparaissent 2x par onglet : une fois pour le client, une fois pour
            // le bloc Parrain). Sans ce dédoublonnage, la construction de $row par
            // nom de colonne plus bas écrase silencieusement la 1ère occurrence par
            // la 2ème (bloc parrain), et les clés "Ville.1" / "Code postal.1"
            // attendues par les importers n'existent jamais.
            // Convention alignée sur pandas : Ville, Ville.1, Ville.2, ...
            $seen = [];
            $headers = array_map(function ($header) use (&$seen) {
                if ($header === '') {
                    return $header;
                }
                if (! isset($seen[$header])) {
                    $seen[$header] = 0;

                    return $header;
                }
                $seen[$header]++;

                return $header.'.'.$seen[$header];
            }, $headers);

            $rows = [];
            for ($i = $headerRowIndex + 1; $i < count($data); $i++) {
                $rawRow = $data[$i];
                if (empty(array_filter($rawRow, fn($v) => $v !== null && $v !== ''))) {
                    continue;
                }

                $row = [];
                foreach ($headers as $colIdx => $header) {
                    if ($header === '') {
                        continue;
                    }
                    $row[$header] = $rawRow[$colIdx] ?? null;
                }
                $rows[] = $row;
            }

            if (! empty($rows)) {
                $result[$sheetName] = [
                    'headers' => $headers,
                    'rows' => $rows,
                ];
            }
        }

        return $result;
    }

    /**
     * @param  class-string<BaseClientImporter>|null  $importerClass
     * @param  (callable(int $processed, int $total, string $sheet): void)|null  $onProgress
     */
    public static function importFile(string $path, ?string $importerClass = null, string $strategy = 'merge', ?callable $onProgress = null): array
    {
        $sheets = static::parseFile($path);
        $results = [];

        $total = array_sum(array_map(fn ($sheetData) => count($sheetData['rows']), $sheets));
        $processedBefore = 0;

        if ($onProgress) {
            $onProgress(0, $total, (string) array_key_first($sheets));
        }

        foreach ($sheets as $sheetName => $sheetData) {
            $class = $importerClass ?? static::detectFromColumns($sheetData['headers']);
            $sheetTotal = count($sheetData['rows']);

            if (! $class) {
                $sample = implode(', ', array_slice(array_filter($sheetData['headers']), 0, 8));
                $results[$sheetName] = [
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 0,
                    'errors' => ["Modèle non reconnu pour « {$sheetName} ». Colonnes : {$sample}…"],
                ];

                $processedBefore += $sheetTotal;
                if ($onProgress) {
                    $onProgress($processedBefore, $total, $sheetName);
                }

                continue;
            }

            /** @var BaseClientImporter $importer */
            $importer = new $class;
            $results[$sheetName] = $importer->import(
                $sheetData['rows'],
                $sheetName,
                $strategy,
                $onProgress
                    ? function (int $done) use ($onProgress, $processedBefore, $total, $sheetName) {
                        $onProgress($processedBefore + $done, $total, $sheetName);
                    }
                    : null,
            );
            $results[$sheetName]['model'] = $class::getName();
            $processedBefore += $sheetTotal;
        }

        return $results;
    }
}
