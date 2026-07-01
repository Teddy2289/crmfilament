<?php

namespace App\Console\Commands;

use App\Services\RingoverCallSyncService;
use App\Services\RingoverService;
use Illuminate\Console\Command;

class SyncRingoverCalls extends Command
{
    protected $signature = 'ringover:sync
                            {--pages=5 : Nombre de pages à récupérer}
                            {--per-page=50 : Appels par page}
                            {--from= : Timestamp de début optionnel}';

    protected $description = 'Synchronise les appels Ringover vers la base de données';

    public function handle(RingoverService $ringover, RingoverCallSyncService $sync): int
    {
        $pages = (int) $this->option('pages');
        $perPage = (int) $this->option('per-page');
        $from = $this->option('from');

        $this->info("Synchronisation Ringover - {$pages} pages x {$perPage} appels...");

        $ringoverUsers = collect($ringover->getUsers())->keyBy('id');
        $created = 0;
        $updated = 0;
        $errors = 0;
        $incompleteTags = 0;

        $bar = $this->output->createProgressBar($pages * $perPage);
        $bar->start();

        for ($page = 1; $page <= $pages; $page++) {
            $filters = ['per_page' => $perPage, 'page' => $page, 'order' => 'desc'];

            if ($from) {
                $filters['from'] = $from;
            }

            $calls = $ringover->getCalls($filters);

            if (empty($calls)) {
                break;
            }

            foreach ($calls as $call) {
                try {
                    $result = $sync->sync($call, $ringoverUsers, 'command');
                    $result['created'] ? $created++ : $updated++;

                    if (! $result['tag_validation']['complete']) {
                        $incompleteTags++;
                    }
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Erreur call {$call['id']}: {$e->getMessage()}");
                }

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->table(
            ['Créés', 'Mis à jour', 'Tags incomplets', 'Erreurs'],
            [[$created, $updated, $incompleteTags, $errors]]
        );

        return self::SUCCESS;
    }
}
