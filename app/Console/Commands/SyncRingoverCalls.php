<?php

namespace App\Console\Commands;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Models\Appel;
use App\Models\User;
use App\Services\RingoverService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncRingoverCalls extends Command
{
    protected $signature = 'ringover:sync
                            {--pages=5 : Nombre de pages a recuperer}
                            {--per-page=50 : Appels par page}
                            {--from= : Timestamp de debut optionnel}';

    protected $description = 'Synchronise les appels Ringover vers la base de donnees';

    public function handle(RingoverService $ringover): int
    {
        $pages = (int) $this->option('pages');
        $perPage = (int) $this->option('per-page');
        $from = $this->option('from');

        $this->info("Synchronisation Ringover - {$pages} pages x {$perPage} appels...");

        $ringoverUsers = collect($ringover->getUsers())->keyBy('id');
        $synced = 0;
        $skipped = 0;
        $errors = 0;

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
                    $result = $this->syncCall($call, $ringoverUsers);
                    $result ? $synced++ : $skipped++;
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
            ['Synchronises', 'Deja existants', 'Erreurs'],
            [[$synced, $skipped, $errors]]
        );

        return self::SUCCESS;
    }

    private function syncCall(array $call, Collection $ringoverUsers): bool
    {
        if (Appel::where('ringover_call_id', (string) $call['id'])->exists()) {
            return false;
        }

        $userId = null;
        $agentNom = null;

        if (! empty($call['user']['id'])) {
            $localUser = User::where('ringover_user_id', (string) $call['user']['id'])->first();

            if ($localUser) {
                $userId = $localUser->id;
                $agentNom = "{$localUser->prenom} {$localUser->nom}";
            } else {
                $ringoverUser = $ringoverUsers->get($call['user']['id']);
                $agentNom = $ringoverUser['name'] ?? "Agent #{$call['user']['id']}";
            }
        }

        $resultat = $this->mapStatut($call['status'] ?? '');
        $type = ($call['direction'] ?? '') === 'inbound'
            ? EventType::Permanence
            : EventType::Appel;

        Appel::create([
            'ringover_call_id' => (string) $call['id'],
            'ringover_user_id' => $call['user']['id'] ?? null,
            'ringover_number_id' => $call['number']['id'] ?? null,
            'ringover_agent_nom' => $agentNom,
            'user_id' => $userId,
            'type' => $type,
            'resultat' => $resultat,
            'date_heure' => Carbon::createFromTimestamp($call['started_at']),
            'duree_secondes' => $call['duration'] ?? null,
            'direction' => $call['direction'] ?? null,
            'numero_appelant' => $call['raw_digits'] ?? null,
            'enregistrement_audio' => $call['recording'] ?? null,
            'commentaire' => $call['comments'][0]['content'] ?? null,
        ]);

        return true;
    }

    private function mapStatut(string $ringoverStatus): ?EventResult
    {
        return match ($ringoverStatus) {
            'answered', 'done' => EventResult::Realise,
            'missed_customer', 'missed' => EventResult::NonAbouti,
            'voicemail' => EventResult::Rappel,
            'blocked', 'abandoned' => EventResult::Annule,
            default => null,
        };
    }
}
