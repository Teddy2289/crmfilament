<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Partenaire;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;

class DolibarrClientsImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public int $created = 0;
    public int $updated = 0;
    public int $errors = 0;
    public array $sheetStats = [];

    public function sheets(): array
    {
        return [
            'CRM LIKE' => new ClientSheetImport('CRM LIKE', $this),
            'CRM AOPIA-ABO' => new ClientSheetImport('CRM AOPIA-ABO', $this),
            'CRM 01FC' => new ClientSheetImport('CRM 01FC', $this),
        ];
    }

    public function onUnknownSheet($sheetName)
    {
        // Ignorer les feuilles inconnues
    }

    public function addStats(string $sheetName, int $created, int $updated, int $errors): void
    {
        $this->created += $created;
        $this->updated += $updated;
        $this->errors += $errors;
        $this->sheetStats[$sheetName] = [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }
}

class ClientSheetImport implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithHeadingRow
{
    public function __construct(
        public string $sheetName,
        public DolibarrClientsImport $parentImport
    ) {}

    public int $created = 0;
    public int $updated = 0;
    public int $errors = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            try {
                $this->processRow($row);
            } catch (\Exception $e) {
                $this->errors++;
                \Log::error('Erreur import ligne', [
                    'sheet' => $this->sheetName,
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->parentImport->addStats($this->sheetName, $this->created, $this->updated, $this->errors);
    }

    protected function processRow($row)
    {
        $partenaire = null;
        if (! empty($row['partenaire_nom'])) {
            $partenaire = Partenaire::where('nom', 'like', '%'.$row['partenaire_nom'].'%')->first();
        }

        $client = Client::updateOrCreate(
            ['email' => $row['email'] ?? null],
            [
                'civilite' => $row['civilite'] ?? null,
                'prenom' => $row['prenom'] ?? null,
                'nom_tiers' => $row['nom'] ?? null,
                'telephone' => $row['telephone'] ?? null,
                'email' => $row['email'] ?? null,
                'adresse' => $row['adresse'] ?? null,
                'code_postal' => $row['code_postal'] ?? null,
                'ville' => $row['ville'] ?? null,
                'departement' => $row['departement'] ?? null,
                'date_naissance' => $this->parseDate($row['date_naissance'] ?? null),
                'partenaire_id' => $partenaire?->id,
                'etat' => $row['statut_formation'] ?? null,
                'extra_data' => [
                    'heures_formation' => $row['heures_formation'] ?? 0,
                    'nombre_parrainages' => $row['nombre_parrainages'] ?? 0,
                    'source_import' => 'dolibarr',
                    'source_sheet' => $this->sheetName,
                    'date_import' => now()->toDateString(),
                ],
            ]
        );

        if ($client->wasRecentlyCreated) {
            $this->created++;
        } else {
            $this->updated++;
        }
    }

    protected function parseDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            if (is_numeric($date)) {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }

            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }
}
