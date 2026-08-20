<?php

namespace App\Console\Commands;

use App\Models\EmailConfiguration;
use App\Services\Email\ImapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestImapConnection extends Command
{
    protected $signature = 'imap:test-connection
        {--config= : ID de la configuration email à tester}
        {--all : Tester toutes les configurations IMAP actives}';

    protected $description = 'Teste uniquement la connexion IMAP sans synchroniser de message';

    public function handle(): int
    {
        $query = EmailConfiguration::query()
            ->where('active', true)
            ->where('sync_enabled', true);

        if ($configId = $this->option('config')) {
            $query->whereKey($configId);
        } elseif (! $this->option('all')) {
            $query->where('is_global', true);
        }

        $configs = $query->get();
        if ($configs->isEmpty()) {
            $this->warn('Aucune configuration IMAP active à tester.');
            return self::SUCCESS;
        }

        $failures = 0;
        foreach ($configs as $config) {
            $label = $config->is_global ? 'Configuration globale' : ($config->email ?: 'Configuration #'.$config->id);
            $this->line("Test de {$label} (#{$config->id})...");

            try {
                // Le constructeur établit la connexion ; aucune méthode de synchronisation n’est appelée.
                new ImapService($config->user, $config);
                $this->info('  Connexion IMAP réussie — aucun message synchronisé.');
            } catch (\Throwable $e) {
                $failures++;
                $this->error('  Échec de connexion IMAP : '.$e->getMessage());
                Log::error('imap:test-connection failed', [
                    'configuration_id' => $config->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }
}
