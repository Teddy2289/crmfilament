<?php

namespace App\Imports;

use App\Models\Client;
use App\Models\Partenaire;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DolibarrClientsImport implements ToCollection, WithHeadingRow
{
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
                    'row' => $row->toArray(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    protected function processRow($row)
    {
        // Mapping des colonnes Dolibarr vers le modèle Client
        // Colonnes attendues: nom, prenom, date_naissance, adresse, code_postal, ville, 
        // telephone, email, partenaire_nom, statut_formation, heures_formation, 
        // nombre_parrainages
        
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
            // Format Excel ou format français dd/mm/YYYY
            if (is_numeric($date)) {
                return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));
            }

            return \Carbon\Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }
}
