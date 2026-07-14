<?php
namespace App\Jobs;

use App\Filament\NsConseil\Resources\ClientResource\Import\ImportResolver;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportClientsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Pas de limite de temps arbitraire côté job : un import de plusieurs
     * dizaines de milliers de lignes peut légitimement prendre plusieurs
     * minutes. On borne large pour éviter qu'un job bloqué reste coincé
     * indéfiniment (ex. deadlock DB), sans reproduire le problème initial.
     */
    public int $timeout = 1800; // 30 min

    public int $tries = 1;

    public function __construct(
        protected string $storedPath,
        protected ?string $importerClass,
        protected string $strategy,
        protected int $userId,
    ) {}

    public function handle(): void
    {
        $absolutePath = Storage::disk('local')->path($this->storedPath);

        try {
            $results = ImportResolver::importFile($absolutePath, $this->importerClass, $this->strategy);
        } catch (\Throwable $e) {
            Log::error('ImportClientsJob : échec de l\'import', [
                'error' => $e->getMessage(),
                'file' => $this->storedPath,
            ]);

            $this->notifyUser(
                title: 'Échec de l\'import',
                body: $e->getMessage(),
                success: false,
            );

            // On nettoie quand même le fichier temporaire avant de relancer
            // l'exception pour que le job apparaisse en échec dans la queue.
            Storage::disk('local')->delete($this->storedPath);

            throw $e;
        }

        Storage::disk('local')->delete($this->storedPath);

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

        $body = "Créés : {$totalCreated} | Mis à jour : {$totalUpdated} | Ignorés : {$totalSkipped}";
        if (! empty($allErrors)) {
            $body .= "\n".count($allErrors).' ligne(s) en erreur.';
        }

        $this->notifyUser(
            title: 'Import terminé',
            body: $body,
            success: empty($allErrors),
        );
    }

    /**
     * En cas d'échec définitif (ex. timeout de 30 min dépassé, exception
     * non rattrapée par handle()), on prévient quand même l'utilisateur
     * plutôt que de le laisser sans nouvelle.
     */
    public function failed(\Throwable $exception): void
    {
        Storage::disk('local')->delete($this->storedPath);

        $this->notifyUser(
            title: 'Échec de l\'import',
            body: 'L\'import a échoué : '.$exception->getMessage(),
            success: false,
        );
    }

    private function notifyUser(string $title, string $body, bool $success): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        $success ? $notification->success() : $notification->danger();

        $notification->sendToDatabase($user);
    }
}
