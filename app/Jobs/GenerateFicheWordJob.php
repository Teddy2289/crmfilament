<?php

namespace App\Jobs;

use App\Models\Appel;
use App\Services\Crm\FicheWordService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateFicheWordJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $appelId
    ) {
    }

    public function handle(FicheWordService $service): void
    {
        $appel = Appel::find($this->appelId);

        if (! $appel || ! $appel->fiche_type) {
            Log::warning("Appel #{$this->appelId} introuvable ou sans type de fiche");
            return;
        }

        try {
            $localPath = $service->genererPourAppel($appel);

            if ($localPath) {
                $destination = now()->format('Y/m');
                $publicUrl = $service->stocker($localPath, $destination);

                $appel->update([
                    'fiche_word_path' => $publicUrl,
                    'fiche_word_generated_at' => now(),
                ]);

                Log::info("Fiche Word générée pour appel #{$this->appelId} : {$publicUrl}");
            }
        } catch (\Exception $e) {
            Log::error("Erreur génération fiche Word pour appel #{$this->appelId} : ".$e->getMessage());
            throw $e;
        }
    }
}
