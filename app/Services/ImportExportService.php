<?php

namespace App\Services;

use App\Models\Prospect;
use App\Models\Client;
use App\Models\Partenaire;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportExportService
{
    protected $fieldMappings = [
        'prospects' => [
            'nom' => 'Nom',
            'email' => 'Email',
            'telephone' => 'Téléphone',
            'statut' => 'Statut',
            'source' => 'Source',
            'region' => 'Région',
            'date_premier_contact' => 'Date premier contact',
        ],
        'clients' => [
            'nom_tiers' => 'Nom',
            'email' => 'Email',
            'telephone' => 'Téléphone',
            'statut' => 'Statut',
            'region' => 'Région',
        ],
        'partenaires' => [
            'nom' => 'Nom',
            'email' => 'Email',
            'telephone' => 'Téléphone',
            'type_partenaire' => 'Type',
            'region' => 'Région',
        ],
    ];

    public function exportData($type, $filters = [])
    {
        $data = match($type) {
            'prospects' => $this->getProspectsData($filters),
            'clients' => $this->getClientData($filters),
            'partenaires' => $this->getPartenaireData($filters),
            default => [],
        };

        return $data;
    }

    private function getProspectsData($filters)
    {
        $query = Prospect::query();

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        return $query->get()->map(function ($prospect) {
            return [
                'Nom' => $prospect->nom,
                'Email' => $prospect->email,
                'Téléphone' => $prospect->telephone,
                'Statut' => $prospect->statut_label ?? $prospect->statut,
                'Source' => $prospect->source,
                'Région' => $prospect->region,
                'Date création' => $prospect->created_at->format('d/m/Y'),
            ];
        })->toArray();
    }

    private function getClientData($filters)
    {
        $query = Client::query();

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        return $query->get()->map(function ($client) {
            return [
                'Nom' => $client->nom_tiers,
                'Email' => $client->email,
                'Téléphone' => $client->telephone,
                'Statut' => $client->statut,
                'Région' => $client->region,
                'Date création' => $client->created_at->format('d/m/Y'),
            ];
        })->toArray();
    }

    private function getPartenaireData($filters)
    {
        $query = Partenaire::query();

        if (isset($filters['type'])) {
            $query->where('type_partenaire', $filters['type']);
        }

        return $query->get()->map(function ($partenaire) {
            return [
                'Nom' => $partenaire->nom,
                'Email' => $partenaire->email,
                'Téléphone' => $partenaire->telephone,
                'Type' => $partenaire->type_partenaire,
                'Région' => $partenaire->region,
                'Date création' => $partenaire->created_at->format('d/m/Y'),
            ];
        })->toArray();
    }

    public function importData($type, $file, $fieldMapping = null)
    {
        $data = $this->parseFile($file);
        $mapping = $fieldMapping ?? $this->fieldMappings[$type] ?? [];
        
        $results = [
            'success' => 0,
            'errors' => 0,
            'error_details' => [],
        ];

        foreach ($data as $rowIndex => $row) {
            try {
                $mappedData = $this->mapFields($row, $mapping);
                $this->validateAndImport($type, $mappedData);
                $results['success']++;
            } catch (\Exception $e) {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $rowIndex + 1,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    private function parseFile($file)
    {
        $extension = $file->getClientOriginalExtension();
        
        if ($extension === 'csv') {
            return $this->parseCsv($file);
        } elseif (in_array($extension, ['xls', 'xlsx'])) {
            return $this->parseExcel($file);
        }

        throw new \Exception('Format de fichier non supporté');
    }

    private function parseCsv($file)
    {
        $data = [];
        $handle = fopen($file->getPathname(), 'r');
        
        if ($handle) {
            $headers = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                $data[] = array_combine($headers, $row);
            }
            
            fclose($handle);
        }
        
        return $data;
    }

    private function parseExcel($file)
    {
        $data = Excel::toArray([], $file);
        return $data[0] ?? [];
    }

    private function mapFields($row, $mapping)
    {
        $mapped = [];
        
        foreach ($mapping as $dbField => $csvHeader) {
            if (isset($row[$csvHeader])) {
                $mapped[$dbField] = $row[$csvHeader];
            }
        }
        
        return $mapped;
    }

    private function validateAndImport($type, $data)
    {
        $validator = match($type) {
            'prospects' => $this->validateProspect($data),
            'clients' => $this->validateClient($data),
            'partenaires' => $this->validatePartenaire($data),
            default => null,
        };

        if ($validator && $validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->importRecord($type, $data);
    }

    private function validateProspect($data)
    {
        return Validator::make($data, [
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|unique:prospects,email',
            'telephone' => 'nullable|string|max:20',
        ]);
    }

    private function validateClient($data)
    {
        return Validator::make($data, [
            'nom_tiers' => 'required|string|max:255',
            'email' => 'nullable|email|unique:clients,email',
            'telephone' => 'nullable|string|max:20',
        ]);
    }

    private function validatePartenaire($data)
    {
        return Validator::make($data, [
            'nom' => 'required|string|max:255',
            'email' => 'nullable|email|unique:partenaires,email',
            'telephone' => 'nullable|string|max:20',
        ]);
    }

    private function importRecord($type, $data)
    {
        match($type) {
            'prospects' => Prospect::create($data),
            'clients' => Client::create($data),
            'partenaires' => Partenaire::create($data),
            default => null,
        };
    }

    public function getFieldMappings($type)
    {
        return $this->fieldMappings[$type] ?? [];
    }

    public function saveImportTemplate($type, $mapping, $name)
    {
        $templates = $this->getImportTemplates();
        $templates[$type][$name] = $mapping;
        
        Storage::put('import-templates.json', json_encode($templates));
    }

    public function getImportTemplates()
    {
        if (!Storage::exists('import-templates.json')) {
            return [];
        }

        return json_decode(Storage::get('import-templates.json'), true);
    }

    public function getImportHistory()
    {
        return [
            'imports' => [
                [
                    'id' => 1,
                    'type' => 'prospects',
                    'file' => 'prospects_import_2024_01_15.csv',
                    'records_imported' => 150,
                    'records_failed' => 5,
                    'imported_at' => '2024-01-15 10:30:00',
                    'imported_by' => 'Jean Dupont',
                ],
                [
                    'id' => 2,
                    'type' => 'clients',
                    'file' => 'clients_import_2024_01_20.xlsx',
                    'records_imported' => 75,
                    'records_failed' => 0,
                    'imported_at' => '2024-01-20 14:15:00',
                    'imported_by' => 'Marie Martin',
                ],
            ],
        ];
    }
}
