<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Partenaire;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ImportDolibarrClients extends Command
{
    protected $signature = 'dolibarr:import-clients {file : Chemin vers le fichier Excel exporté de Dolibarr}';
    protected $description = 'Importer les clients depuis un export Excel Dolibarr';

    public function handle()
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("Le fichier n'existe pas : {$filePath}");
            return 1;
        }

        $this->info("Import des clients depuis : {$filePath}");

        try {
            $import = new \App\Imports\DolibarrClientsImport();
            Excel::import($import, $filePath);

            $this->info("Import terminé avec succès !");
            $this->info("Clients créés : {$import->created}");
            $this->info("Clients mis à jour : {$import->updated}");
            $this->info("Erreurs : {$import->errors}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'import : {$e->getMessage()}");
            Log::error('Import Dolibarr clients error', [
                'file' => $filePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
