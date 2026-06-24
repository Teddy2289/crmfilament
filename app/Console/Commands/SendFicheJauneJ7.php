<?php

namespace App\Console\Commands;

use App\Jobs\SendFicheJauneJ7Job;
use Illuminate\Console\Command;

class SendFicheJauneJ7 extends Command
{
    protected $signature = 'crm:send-fiche-jaune-j7';
    protected $description = 'Envoie automatiquement les fiches jaunes J+7 aux commerciaux';

    public function handle(): int
    {
        $this->info('Lancement du traitement des fiches jaunes J+7...');
        
        $job = new SendFicheJauneJ7Job();
        $job->handle(app(\App\Services\Crm\FicheWordService::class));
        
        $this->info('Traitement terminé.');

        return self::SUCCESS;
    }
}
