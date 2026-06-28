<?php

namespace App\Console\Commands;

use App\Filament\NsConseil\Resources\ClientResource\Import\ImportResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportBaseClients extends Command
{
    protected $signature = 'import:base-clients {path?} {--strategy=merge}';

    protected $description = 'Importer les clients depuis le fichier base clients';

    public function handle(): int
    {
        $path = $this->argument('path') ?? storage_path('app/base_clients.xlsx');
        $strategy = $this->option('strategy');

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return 1;
        }

        $this->info("Importation depuis : {$path}");
        $this->info("Stratégie : {$strategy}");

        try {
            $results = ImportResolver::importFile($path, null, $strategy);

            $totalCreated = 0;
            $totalUpdated = 0;
            $totalSkipped = 0;
            $allErrors = [];

            foreach ($results as $sheetName => $result) {
                $totalCreated += $result['created'];
                $totalUpdated += $result['updated'];
                $totalSkipped += $result['skipped'];
                foreach ($result['errors'] as $err) {
                    $allErrors[] = "[{$sheetName}] {$err}";
                }
            }

            $this->info("✓ Import terminé");
            $this->info("  Créés : {$totalCreated}");
            $this->info("  Mis à jour : {$totalUpdated}");
            $this->info("  Ignorés : {$totalSkipped}");

            if (! empty($allErrors)) {
                $this->warn(count($allErrors).' erreur(s) :');
                foreach (array_slice($allErrors, 0, 10) as $error) {
                    $this->line("  - {$error}");
                }
                if (count($allErrors) > 10) {
                    $this->line("  ... et ".(count($allErrors) - 10).' autres');
                }
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Erreur lors de l'import : {$e->getMessage()}");
            return 1;
        }
    }
}
