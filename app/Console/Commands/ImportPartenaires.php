<?php

namespace App\Console\Commands;

use App\Filament\NsConseil\Resources\PartenaireResource\Import\PartenaireImportResolver;
use Illuminate\Console\Command;

class ImportPartenaires extends Command
{
    protected $signature = 'import:partenaires {path?} {--strategy=merge}';

    protected $description = 'Importer les partenaires depuis le fichier Excel';

    public function handle(): int
    {
        $path = $this->argument('path') ?? storage_path('app/partenaires.xlsx');
        $strategy = $this->option('strategy');

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return 1;
        }

        $this->info("Importation depuis : {$path}");
        $this->info("Stratégie : {$strategy}");

        try {
            $result = PartenaireImportResolver::importFile($path, [], $strategy);

            $this->info("✓ Import terminé");
            $this->info("  Créés : {$result['created']}");
            $this->info("  Mis à jour : {$result['updated']}");
            $this->info("  Ignorés : {$result['skipped']}");

            if (! empty($result['errors'])) {
                $this->warn(count($result['errors']).' erreur(s) :');
                foreach (array_slice($result['errors'], 0, 10) as $error) {
                    $this->line("  - {$error}");
                }
                if (count($result['errors']) > 10) {
                    $this->line("  ... et ".(count($result['errors']) - 10).' autres');
                }
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error("Erreur lors de l'import : {$e->getMessage()}");
            return 1;
        }
    }
}
