<?php

namespace App\Console\Commands;

use App\Models\EmailConfiguration;
use App\Models\User;
use App\Services\Email\ImapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:sync {--config= : ID de la configuration} {--limit= : Nombre d\'emails à synchroniser} {--all : Synchroniser toutes les configurations actives}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les emails depuis le serveur IMAP';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $configId = $this->option('config');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $syncAll = $this->option('all');

        $configs = $this->getConfigsToSync($configId, $syncAll);

        if ($configs->isEmpty()) {
            $this->warn('Aucune configuration à synchroniser');
            return self::SUCCESS;
        }

        $totalStats = [
            'configs' => 0,
            'synced' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $this->info("Début de la synchronisation pour {$configs->count()} configuration(s)...");

        foreach ($configs as $config) {
            $user = $config->user;
            $userLabel = $config->is_global ? 'Configuration globale' : ($user?->email ?? 'Utilisateur inconnu');
            
            $this->info("Synchronisation pour {$userLabel}...");

            try {
                $service = new ImapService($user, $config);
                $stats = $service->syncEmails($limit);

                $totalStats['configs']++;
                $totalStats['synced'] += $stats['synced'];
                $totalStats['skipped'] += $stats['skipped'];
                $totalStats['errors'] += $stats['errors'];

                $this->info("  - Synced: {$stats['synced']}");
                $this->info("  - Skipped: {$stats['skipped']}");
                $this->info("  - Errors: {$stats['errors']}");

            } catch (\Exception $e) {
                $this->error("Erreur pour {$userLabel}: {$e->getMessage()}");
                $totalStats['errors']++;
                Log::error("Erreur sync emails config {$config->id}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('=== Résumé ===');
        $this->info("Configurations traitées: {$totalStats['configs']}");
        $this->info("Emails synchronisés: {$totalStats['synced']}");
        $this->info("Emails ignorés: {$totalStats['skipped']}");
        $this->info("Erreurs: {$totalStats['errors']}");

        return self::SUCCESS;
    }

    /**
     * Récupère les configurations à synchroniser
     */
    protected function getConfigsToSync(?string $configId, bool $syncAll): \Illuminate\Support\Collection
    {
        $query = EmailConfiguration::query();

        if ($configId) {
            $query->where('id', $configId);
        }

        // Filtrer les configurations actives avec synchronisation activée
        $query->where('active', true)
            ->where('sync_enabled', true);

        // Si pas --all, prendre seulement les configurations non globales
        if (! $syncAll) {
            $query->where('is_global', false);
        }

        return $query->get();
    }
}
