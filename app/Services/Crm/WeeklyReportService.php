<?php

namespace App\Services\Crm;

use App\Enums\ProspectStatut;

use App\Enums\RendezVousStatut;
use App\Models\Appel;
use App\Models\Opportunite;
use App\Models\Partenaire;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Construit les données de reporting hebdomadaire (CDC WF5 / WF6 / §7bis).
 */
class WeeklyReportService
{
    public function periode(): array
    {
        $debut = CarbonImmutable::now()->subWeek()->startOfWeek();
        $fin = $debut->endOfWeek();

        return [$debut, $fin];
    }

    /**
       * Rapport enrichi pour un téléprospecteur (CDC §7bis).
     */
    public function pourTeleprospecteur(User $user): array
    {
        [$debut, $fin] = $this->periode();

        $prospectsParStatut = Prospect::query()
            ->where('teleprospecteur_id', $user->id)
            ->selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')
            ->pluck('total', 'statut')
            ->toArray();

        $appelsSemaine = Appel::query()
            ->where(function ($q) use ($user) {
                $q->where('phoning_agent_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->whereBetween('date_heure', [$debut, $fin])
            ->count();


            $cseJoints = Appel::query()
            ->where(function ($q) use ($user) {
                $q->where('phoning_agent_id', $user->id)
                    ->orWhere('user_id', $user->id);
            })
            ->whereBetween('date_heure', [$debut, $fin])
            ->whereIn('phoning_status', ['STD_Joint', 'CSE_NR', 'RP', 'RPC', 'QF'])
            ->count();

        $tauxConversion = $appelsSemaine > 0
            ? round(($cseJoints / $appelsSemaine) * 100, 1)
            : 0;

        $baseAC = Prospect::query()
            ->where('teleprospecteur_id', $user->id)
            ->where('statut', ProspectStatut::AC)
            ->count();

        $rappelsAujourdhui = Prospect::query()
            ->where('teleprospecteur_id', $user->id)
            ->whereNotNull('rappel_planifie_at')
            ->whereDate('rappel_planifie_at', CarbonImmutable::now())
            ->count();

            $prochains_rappels = Prospect::query()
            ->where('teleprospecteur_id', $user->id)
            ->whereNotNull('rappel_planifie_at')
            ->where('rappel_planifie_at', '>=', CarbonImmutable::now())
            ->where('rappel_planifie_at', '<=', CarbonImmutable::now()->endOfWeek())
            ->orderBy('rappel_planifie_at')
            ->take(10)
            ->get(['nom', 'telephone', 'rappel_planifie_at']);

        return [
            'user' => $user,
            'role' => 'teleprospecteur',
            'periode' => [$debut, $fin],
            'appels_semaine' => $appelsSemaine,
            'rappels_a_venir' => $rappelsAVenir,
            'prospects_par_statut' => $this->labelliserProspects($prospectsParStatut),
             'cse_joints' => $cseJoints,
            'qf' => $prospectsParStatut[ProspectStatut::QF->value] ?? 0,
            'taux_conversion' => $tauxConversion,
            'base_ac' => $baseAC,
            'rappels_aujourd_hui' => $rappelsAujourdhui,
            'rp' => $prospectsParStatut[ProspectStatut::RP->value] ?? 0,
            'rpc' => $prospectsParStatut[ProspectStatut::RPC->value] ?? 0,
            'std_nr' => $prospectsParStatut[ProspectStatut::STD_NR->value] ?? 0,
            'ko' => $prospectsParStatut[ProspectStatut::KO->value] ?? 0,
            'prospects_par_statut' => $this->labelliserProspects($prospectsParStatut),
            'prochains_rappels' => $prochains_rappels,
            'rappels_a_venir' => $prochains_rappels->count(),
        ];
    }

    /**
      * Rapport enrichi pour un commercial (CDC §7bis).
     */
    public function pourCommercial(User $user): array
    {
        [$debut, $fin] = $this->periode();
          $semaineProchaine = [CarbonImmutable::now()->startOfWeek(), CarbonImmutable::now()->endOfWeek()];

        $rdvSemaine = RendezVous::query()
            ->where('commercial_id', $user->id)
            ->whereBetween('date_heure', [$debut, $fin])
             ->get();
  $rdvRealises = $rdvSemaine->where('statut', RendezVousStatut::Realise)->count();
        $rdvAnnules = $rdvSemaine->where('statut', RendezVousStatut::Annule)->count();
        $rdvDecales = $rdvSemaine->where('statut', RendezVousStatut::Decale)->count();
        $totalRdv = $rdvSemaine->count();
        $noShow = $totalRdv > 0 ? round(($rdvAnnules / $totalRdv) * 100, 1) : 0;

        $rdvAVenir = RendezVous::query()
            ->where('commercial_id', $user->id)
 ->whereBetween('date_heure', $semaineProchaine)
            ->whereIn('statut', [RendezVousStatut::Planifie, RendezVousStatut::Decale])
            ->orderBy('date_heure')
            ->take(10)
            ->get();
        $partenairesActifs = Partenaire::query()
            ->whereHas('rendezVous', fn ($q) => $q->where('commercial_id', $user->id))
            ->actifs()
             ->get(['id', 'nom', 'statut']);

        $prospectsEnAttente = Prospect::query()
            ->where('commercial_id', $user->id)
            ->whereIn('statut', [ProspectStatut::RP, ProspectStatut::RPC])
            ->count();

             $nouveauxProspects = Prospect::query()
            ->where('commercial_id', $user->id)
            ->whereBetween('created_at', [$debut, $fin])
            ->count();

        $opportunitesActives = Opportunite::query()
            ->where('assigne_a', $user->id)
            ->actives()
            ->count();

        return [
            'user' => $user,
            'role' => 'commercial',
            'periode' => [$debut, $fin],
                 'rdv_total' => $totalRdv,
            'rdv_realises' => $rdvRealises,
            'rdv_annules' => $rdvAnnules,
            'rdv_decales' => $rdvDecales,
            'taux_no_show' => $noShow,
            'rdv_a_venir' => $rdvAVenir,
            'partenaires_actifs' => $partenairesActifs,
            'partenaires_actifs_count' => $partenairesActifs->count(),
            'prospects_en_attente' => $prospectsEnAttente,
            'nouveaux_prospects' => $nouveauxProspects,
            'opportunites_actives' => $opportunitesActives,
            // Compat anciennes clés
            'rdv_semaine' => $totalRdv
        ];
    }

    /**
     * Rapport consolidé pour le Team Leader (CDC §7bis).
     */
    public function pourTeamLeader(User $user): array
    {
        [$debut, $fin] = $this->periode();

        $tps = $this->destinataires(User::ROLE_TELEPROSPECTEUR);
        $commerciaux = $this->destinataires(User::ROLE_COMMERCIAL);

        $phoningConsolide = $tps->map(fn (User $tp) => $this->pourTeleprospecteur($tp));

        $commercialConsolide = $commerciaux->map(fn (User $c) => $this->pourCommercial($c));

        // Alertes
        $tpSansAppel2j = User::query()
            ->where('actif', true)
            ->where('role_cache', User::ROLE_TELEPROSPECTEUR)
            ->whereDoesntHave('appels', fn ($q) => $q->where('date_heure', '>=', CarbonImmutable::now()->subDays(2)))
            ->get(['id', 'prenom', 'nom']);

        $rpcSansSuite = Prospect::query()
            ->where('statut', ProspectStatut::RPC)
            ->where('updated_at', '<', CarbonImmutable::now()->subDays(5))
            ->count();

        $rpNonTraites = Prospect::query()
            ->whereNotNull('rappel_planifie_at')
            ->where('rappel_planifie_at', '<', CarbonImmutable::now())
            ->whereIn('statut', [ProspectStatut::RP])
            ->count();

        $qfAValider = Prospect::query()
            ->where('statut', ProspectStatut::QF)
            ->where('qf_valide', false)
            ->count();

        return [
            'user' => $user,
            'role' => 'team_leader',
            'periode' => [$debut, $fin],
            'phoning_consolide' => $phoningConsolide,
            'commercial_consolide' => $commercialConsolide,
            'alertes' => [
                'tp_sans_appel_2j' => $tpSansAppel2j,
                'rpc_sans_suite_5j' => $rpcSansSuite,
                'rp_non_traites' => $rpNonTraites,
                'qf_a_valider' => $qfAValider,
            ],
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function destinataires(string $role): Collection
    {
        return User::query()
            ->where('actif', true)
            ->where('role_cache', $role)
            ->whereNotNull('email')
            ->get();
    }

    private function labelliserProspects(array $parStatut): array
    {
        return collect(ProspectStatut::cases())
            ->mapWithKeys(fn (ProspectStatut $s) => [
                $s->label() => $parStatut[$s->value] ?? 0,
            ])
            ->toArray();
    }
}
