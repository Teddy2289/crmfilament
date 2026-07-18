<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Partenaire;
use Illuminate\Console\Command;

class RelierClientsPartenaires extends Command
{
    protected $signature = 'clients:relier-partenaires {--dry-run : Afficher les rattachements possibles sans modifier la base}';

    protected $description = "Retente le rattachement partenaire des clients marqués « partenaire non rattaché », avec une correspondance plus tolérante (accents, casse, numéro de département)";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $clients = Client::partenaireNonRattaches()->get();

        $this->info("{$clients->count()} client(s) marqué(s) « partenaire non rattaché ».");

        $rattaches = 0;
        $toujoursNonRattaches = 0;

        foreach ($clients as $client) {
            $nomenclature = $client->nomenclature_partenaire_import;

            if (! $nomenclature) {
                $toujoursNonRattaches++;
                continue;
            }

            $partenaire = Partenaire::resolveByNomenclature($nomenclature);

            if (! $partenaire) {
                $toujoursNonRattaches++;
                continue;
            }

            $this->line("  ✓ #{$client->id} {$client->nom_tiers} — « {$nomenclature} » → {$partenaire->nom} (#{$partenaire->id})");
            $rattaches++;

            if ($dryRun) {
                continue;
            }

            $extraData = $client->extra_data ?? [];
            $extraData['partenaire_import']['statut'] = 'rattache';

            $client->update([
                'partenaire_id' => $partenaire->id,
                'extra_data' => $extraData,
            ]);
        }

        $this->newLine();
        $this->info(($dryRun ? '[dry-run] ' : '')."{$rattaches} client(s) rattaché(s), {$toujoursNonRattaches} toujours sans correspondance.");

        return 0;
    }
}
