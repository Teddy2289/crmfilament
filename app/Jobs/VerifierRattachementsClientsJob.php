<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Partenaire;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Retente le rattachement partenaire des clients marqués « partenaire non
 * rattaché » — l'import ne fait qu'une correspondance stricte sur la
 * nomenclature, donc des clients restent orphelins si le partenaire a été
 * créé/renommé après coup. Même logique que la commande CLI
 * `clients:relier-partenaires`, déclenchable ici depuis l'UI.
 */
class VerifierRattachementsClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(protected int $userId) {}

    public function handle(): void
    {
        $rattaches = 0;
        $toujoursNonRattaches = 0;

        Client::partenaireNonRattaches()
            ->chunkById(200, function ($clients) use (&$rattaches, &$toujoursNonRattaches) {
                foreach ($clients as $client) {
                    $nomenclature = $client->nomenclature_partenaire_import;
                    $partenaire = $nomenclature ? Partenaire::resolveByNomenclature($nomenclature) : null;

                    if (! $partenaire) {
                        $toujoursNonRattaches++;

                        continue;
                    }

                    $extraData = $client->extra_data ?? [];
                    $extraData['partenaire_import']['statut'] = 'rattache';

                    $client->update([
                        'partenaire_id' => $partenaire->id,
                        'extra_data' => $extraData,
                    ]);

                    $rattaches++;
                }
            });

        $this->notifyUser($rattaches, $toujoursNonRattaches);
    }

    public function failed(\Throwable $exception): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Échec de la vérification des rattachements')
            ->body($exception->getMessage())
            ->danger()
            ->sendToDatabase($user);
    }

    private function notifyUser(int $rattaches, int $toujoursNonRattaches): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        Notification::make()
            ->title('Vérification des rattachements terminée')
            ->body("{$rattaches} client(s) rattaché(s) | {$toujoursNonRattaches} toujours sans correspondance.")
            ->success()
            ->sendToDatabase($user);
    }
}
