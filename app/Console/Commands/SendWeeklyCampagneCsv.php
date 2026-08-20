<?php
namespace App\Console\Commands;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
class SendWeeklyCampagneCsv extends Command
{
    protected $signature = 'crm:weekly-campagne-csv {--to=admin@ns-conseil.com : Destinataire du rapport} {--dry-run : Générer le CSV sans envoyer d’email} {--output= : Chemin de sortie du CSV en simulation}';
    protected $description = 'Envoie chaque semaine le CSV des appels des campagnes';
    public function handle(): int
    {
        $to = (string) $this->option('to');
        $from = now()->subWeek()->startOfDay();
        $toDate = now();
        $ids = CampagnePhoning::query()->pluck('id');
        $appels = Appel::query()->whereIn('campagne_id', $ids)
            ->whereBetween('date_heure', [$from, $toDate])
            ->with(['appelable', 'user', 'campagne'])
            ->orderBy('date_heure')->get()
            ->unique(fn ($appel) => (string) $appel->appelable_type . ':' . $appel->appelable_id)->values();
        $csv = "\xEF\xBB\xBF";
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, ['Prospect / contact', 'Type', 'Téléphone', 'Ville', 'Campagne', 'Agent', 'Dernier appel', 'Statut appel', 'Statut prospect', 'Commentaire', 'Note']);
        foreach ($appels as $appel) {
            $contact = $appel->appelable;
            fputcsv($stream, [$contact?->nom ?? $contact?->raison_sociale ?? 'Contact #'.$appel->appelable_id, class_basename((string) $appel->appelable_type), $contact?->telephone ?? $appel->numero_appelant ?? '', $contact?->ville ?? '', $appel->campagne?->nom ?? '', trim(($appel->user?->prenom ?? '').' '.($appel->user?->nom ?? '')), optional($appel->date_heure)->format('d/m/Y H:i'), (string) $appel->phoning_status, optional($contact?->statut)->label(), $appel->commentaire ?? '', $appel->phoning_notes ?? '']);
        }
        rewind($stream);
        $csv .= stream_get_contents($stream);
        fclose($stream);
        if ($this->option('dry-run')) {
            $path = $this->option('output') ?: storage_path('app/campagnes-appels-hebdomadaire-simulation.csv');
            file_put_contents($path, $csv);
            $this->info("Simulation terminée : {$path} ({$appels->count()} contacts uniques).");
            return self::SUCCESS;
        }
        Mail::raw("Veuillez trouver en pièce jointe le rapport CSV hebdomadaire des appels, période du {$from->format('d/m/Y H:i')} au {$toDate->format('d/m/Y H:i')}.", function ($message) use ($to, $csv): void {
            $message->to($to)->subject('Rapport hebdomadaire des campagnes d’appels')->attachData($csv, 'campagnes-appels-hebdomadaire.csv', ['mime' => 'text/csv']);
        });
        $this->info("CSV hebdomadaire envoyé à {$to} ({$appels->count()} contacts uniques).");
        return self::SUCCESS;
    }
}
