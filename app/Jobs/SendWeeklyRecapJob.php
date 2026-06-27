<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\Prospect;
use App\Models\Partenaire;
use App\Models\Appel;
use App\Models\RendezVous;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;

class SendWeeklyRecapJob implements ShouldQueue
{
    use Queueable;

    public string $targetAudience;

    /**
     * Create a new job instance.
     */
    public function __construct(string $targetAudience = 'teleprospecteurs')
    {
        $this->targetAudience = $targetAudience; // 'teleprospecteurs' ou 'commerciaux'
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startDate = Carbon::now()->subWeek()->startOfWeek();
        $endDate = Carbon::now()->subWeek()->endOfWeek();

        if ($this->targetAudience === 'teleprospecteurs') {
            $this->sendTeleprospecteursRecap($startDate, $endDate);
        } else {
            $this->sendCommerciauxRecap($startDate, $endDate);
        }
    }

    protected function sendTeleprospecteursRecap(Carbon $startDate, Carbon $endDate): void
    {
        $teleprospecteurs = User::role('teleprospecteur')->get();

        foreach ($teleprospecteurs as $teleprospecteur) {
            $stats = $this->getTeleprospecteurStats($teleprospecteur->id, $startDate, $endDate);

            Mail::to($teleprospecteur->email)->send(new \App\Mail\WeeklyTeleprospecteurRecap(
                $teleprospecteur,
                $stats,
                $startDate,
                $endDate
            ));
        }
    }

    protected function sendCommerciauxRecap(Carbon $startDate, Carbon $endDate): void
    {
        $commerciaux = User::role('commercial')->get();

        foreach ($commerciaux as $commercial) {
            $stats = $this->getCommercialStats($commercial->id, $startDate, $endDate);

            Mail::to($commercial->email)->send(new \App\Mail\WeeklyCommercialRecap(
                $commercial,
                $stats,
                $startDate,
                $endDate
            ));
        }
    }

    protected function getTeleprospecteurStats(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'appels_realises' => Appel::where('user_id', $userId)
                ->whereBetween('date_heure', [$startDate, $endDate])
                ->count(),
            'prospects_contactes' => Prospect::where('teleprospecteur_id', $userId)
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
            'rdv_planifies' => RendezVous::where('commercial_id', $userId)
                ->whereBetween('date_heure', [$startDate, $endDate])
                ->count(),
            'conversions_qf' => Prospect::where('teleprospecteur_id', $userId)
                ->where('statut', 'QF')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
            'conversions_partenaire' => Prospect::where('teleprospecteur_id', $userId)
                ->whereNotNull('converti_partenaire_id')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
        ];
    }

    protected function getCommercialStats(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        return [
            'rdv_realises' => RendezVous::where('commercial_id', $userId)
                ->where('resultat', 'Réalisé')
                ->whereBetween('date_heure', [$startDate, $endDate])
                ->count(),
            'prospects_qf' => Prospect::where('commercial_id', $userId)
                ->where('statut', 'QF')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
            'conversions_partenaire' => Prospect::where('commercial_id', $userId)
                ->whereNotNull('converti_partenaire_id')
                ->whereBetween('updated_at', [$startDate, $endDate])
                ->count(),
            'partenaires_actifs' => Partenaire::where('commercial_id', $userId)
                ->where('statut', 'convention_engagement')
                ->count(),
            'opportunites_en_cours' => DB::table('opportunites')
                ->where('commercial_id', $userId)
                ->whereIn('statut', ['nouveau', 'en_cours_evaluation', 'contacte', 'rdv_planifie', 'en_negociation'])
                ->count(),
        ];
    }
}
