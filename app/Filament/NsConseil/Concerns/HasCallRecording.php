<?php

namespace App\Filament\NsConseil\Concerns;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\ProspectStatut;
use App\Enums\StatutCampagneProspection;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Services\Crm\CrmSettingsService;
use App\Services\ProspectionMailService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Concern : enregistrement résultat appel, mise à jour contact, fiches récap.
 * Extrait de PhoningWorkflow pour atteindre la cible ≤ 200 lignes.
 */
trait HasCallRecording
{
    // ── Complétion campagne ──────────────────────────────────────────

    protected function checkCampagneCompletion(): void
    {
        if (! $this->currentCampagneId) {
            return;
        }

        $campagne = CampagnePhoning::find($this->currentCampagneId);
        if (! $campagne || $campagne->statut !== 'active') {
            return;
        }

        if ($campagne->estTerminee()) {
            $campagne->update(['statut' => 'terminee']);

            Notification::make()
                ->title('Campagne terminée !')
                ->body("Tous les contacts de « {$campagne->nom} » ont été traités.")
                ->success()
                ->duration(8000)
                ->send();
        }
    }

    // ── Mise à jour contacts ─────────────────────────────────────────

    protected function updateArtisan(): void
    {
        $artisan = $this->currentContact;
        $nouveauStatut = match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => StatutCampagneProspection::RP,
            'std_nr', 'cse_nr'       => StatutCampagneProspection::NR,
            'ko' => StatutCampagneProspection::KO ?? StatutCampagneProspection::NR,
            default => StatutCampagneProspection::AC,
        };
        $artisan->changerStatut($nouveauStatut, $this->commentaires);
        $artisan->marquerContact();
        if ($this->statut_resultat === 'rp' && $this->rappel_date) {
            $artisan->ajouterNote(
                'Rappel programmé le ' . $this->rappel_date .
                ($this->rappel_heure ? ' ' . $this->rappel_heure : '')
            );
        }
    }

    protected function updatePartenaire(): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . '] ' . $this->getResultLabel();
        if ($this->commentaires) {
            $note .= "\n{$this->commentaires}";
        }
        $this->currentContact->ajouterNote($note);
    }

    protected function updateParticulier(): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . '] ' . $this->getResultLabel();
        if ($this->commentaires) {
            $note .= ' - ' . $this->commentaires;
        }
        $this->currentContact->update([
            'notes' => ($this->currentContact->notes ? $this->currentContact->notes . "\n" : '') . $note,
        ]);
    }

    protected function updateClient(): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . '] ' . $this->getResultLabel();
        if ($this->commentaires) {
            $note .= ' — ' . $this->commentaires;
        }
        $extra = $this->currentContact->extra_data ?? [];
        $extra['historique_appels'][] = $note;
        $this->currentContact->update(['extra_data' => $extra]);
    }

    protected function updateProspect(): void
    {
        $prospect   = $this->currentContact;
        $statutMeta = StatutPhoning::where('model_type', 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        $nouveauStatut = $statutMeta?->pipeline_statut
            ? ProspectStatut::tryFrom($statutMeta->pipeline_statut)
            : null;

        if (! $nouveauStatut) {
            $nouveauStatut = ProspectStatut::AC;
        }

        $note = $this->getResultLabel();
        if ($this->commentaires) {
            $note .= ' — ' . $this->commentaires;
        }

        $updateData = $this->getProspectInterlocuteurUpdateData();
        if (! empty($updateData)) {
            $prospect->update($updateData);
        }

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->marquerKO($note);
        } else {
            $prospect->changerStatut($nouveauStatut, $note);
        }

        $prospect->marquerContact();
        if (! $prospect->teleprospecteur_id) {
            $prospect->assignerTeleprospecteur(Auth::id());
        }

        $this->persistProspectInterlocuteurFields($prospect);

        $rdv = null;

        if ($this->statut_resultat === 'rdv') {
            $rdv = $this->creerRendezVous($prospect);
            Log::info('MAIL DEBUG: creerRendezVous', [
                'rappel_date' => $this->rappel_date,
                'rappel_heure' => $this->rappel_heure,
                'rdv_created' => $rdv !== null,
                'rdv_id'      => $rdv?->id,
            ]);
        }

        app(ProspectionMailService::class)->envoyerPourStatut(
            $this->statut_resultat,
            $prospect,
            $this->buildProspectionMailContext($rdv)
        );

        if ($this->rappel_date) {
            $this->appliquerRappelProspect($prospect);
        } elseif ($statutMeta?->delai_rappel_jours) {
            $prospect->programmerRappel(now()->addDays($statutMeta->delai_rappel_jours));
        } elseif ($statutMeta?->compte_comme_tentative) {
            $max       = (int) app(CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
            $tentatives = $this->compterTentativesNonAbouties($prospect) + 1;
            if ($tentatives >= $max) {
                $stdNr = ProspectStatut::tryFrom('STD_NR') ?? ProspectStatut::STD_NR;
                $prospect->changerStatut($stdNr, "{$max} tentatives sans réponse");
                $prospect->marquerDifficile();
                $jours = (int) app(CrmSettingsService::class)->get('prospection.std_nr_reminder_days', 2);
                $prospect->programmerRappel(now()->addDays($jours));
            } else {
                $heures = (int) app(CrmSettingsService::class)->get('prospection.retry_reminder_hours', 3);
                $prospect->programmerRappel(now()->addHours($heures));
            }
        }
    }

    protected function creerRendezVous(Prospect $prospect): ?\App\Models\RendezVous
    {
        if (! $this->rappel_date) {
            return null;
        }

        $dateHeure = $this->rappel_date . ' ' . ($this->rappel_heure ?: '09:00');

        return \App\Models\RendezVous::create([
            'rdvable_type'        => Prospect::class,
            'rdvable_id'          => $prospect->id,
            'date_heure'          => $dateHeure,
            'lieu'                => $this->lieu_rdv ?: null,
            'statut'              => \App\Enums\RendezVousStatut::Planifie,
            'commercial_id'       => $prospect->commercial_id,
            'teleprospecteur_id'  => Auth::id(),
            'interlocuteur_nom'   => $this->interlocuteur_nom ?: $prospect->fallback_interlocuteur_nom,
            'interlocuteur_tel'   => $this->interlocuteur_telephone ?: $prospect->fallback_interlocuteur_telephone,
            'interlocuteur_email' => $this->interlocuteur_email ?: $prospect->fallback_interlocuteur_email,
        ]);
    }

    protected function appliquerRappelProspect(Prospect $prospect): void
    {
        try {
            $fmt = 'Y-m-d' . ($this->rappel_heure ? ' H:i' : '');
            $val = $this->rappel_date . ($this->rappel_heure ? ' ' . $this->rappel_heure : '');
            $dt  = \DateTime::createFromFormat($fmt, $val);
            if ($dt) {
                $prospect->programmerRappel($dt);
            }
        } catch (\Exception) {
        }
    }

    // ── Fiches récap ─────────────────────────────────────────────────

    protected function determineFicheType(): ?string
    {
        return $this->getSelectedStatus()?->fiche_type;
    }

    protected function buildFicheData(string $ficheType): array
    {
        $info            = $this->currentContactData;
        $prospect        = $this->currentContact;
        $teleprospecteur = Auth::user();
        $commercial      = $prospect instanceof Prospect ? $prospect->commercial : null;

        $base = [
            'raison_sociale'          => $info['nom'] ?? null,
            'secteur_activite'        => $info['secteur_activite'] ?? null,
            'effectif_total'          => $info['nb_salaries'] ?? null,
            'adresse'                 => $info['adresse_complete'] ?? null,
            'interlocuteur_nom'       => $this->interlocuteur_nom ?: ($info['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction'  => $this->interlocuteur_fonction ?: ($info['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone' => $this->interlocuteur_telephone ?: ($info['interlocuteur_telephone'] ?? null),
            'interlocuteur_email'     => $this->interlocuteur_email ?: ($info['interlocuteur_email'] ?? null),
            'teleprospecteur_nom'     => $teleprospecteur ? trim("{$teleprospecteur->prenom} {$teleprospecteur->nom}") : null,
            'commercial_nom'          => $commercial ? trim("{$commercial->prenom} {$commercial->nom}") : null,
            'date_appel'              => now()->format('d/m/Y'),
        ];

        return match ($ficheType) {
            'bleue' => array_merge($base, [
                'date_rdv'                  => $this->rappel_date ?: null,
                'heure_rdv'                 => $this->rappel_heure ?: null,
                'lieu_rdv'                  => $this->lieu_rdv ?: null,
                'invitation_agenda_envoyee' => $this->invitation_agenda_envoyee ? 'Oui' : 'Non',
                'enregistrement_appel_joint' => $this->enregistrement_appel_joint ? 'Oui' : 'Non',
                'enregistrement_raison'     => $this->enregistrement_raison ?: null,
                'besoins_exprimes'          => $this->besoins_exprimes ?: null,
                'objections_soulevees'      => $this->objections_soulevees ?: null,
                'points_attention_rdv'      => $this->points_attention_rdv ?: null,
                'notes_interlocuteur'       => $this->commentaires ?: null,
            ]),
            'jaune' => array_merge($base, [
                'commentaires' => $this->commentaires ?: null,
                'date_rappel'  => $this->rappel_date ?: now()->addDays(7)->format('Y-m-d'),
                'heure_rappel' => $this->rappel_heure ?: null,
            ]),
            'verte' => array_merge($base, [
                'presence_cse'        => $this->presence_cse ?: null,
                'jour_dispo_appel'    => $this->jour_dispo_appel ?: null,
                'commentaires'        => $this->commentaires ?: null,
                'date_rdv_a_prendre'  => $this->rappel_date ?: null,
                'heure_rdv_a_prendre' => $this->rappel_heure ?: null,
            ]),
            default => [],
        };
    }

    // ── Journal d'appel ──────────────────────────────────────────────

    protected function enregistrerAppel(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $eventResult = match ($this->statut_resultat) {
            'nrp', 'fax', 'std_nr', 'cse_nr'     => EventResult::NonAbouti,
            'supp', 'cse_hz', 'ko'                => EventResult::Annule,
            'rdv', 'rapl_elu', 'rapl_std', 'rp'   => EventResult::Rappel,
            default                                => EventResult::Realise,
        };

        $ficheType     = $this->determineFicheType();
        $dateHeure     = filled($this->ringoverCallStartedAt) ? Carbon::parse($this->ringoverCallStartedAt) : now();
        $dateFin       = filled($this->ringoverCallEndedAt)   ? Carbon::parse($this->ringoverCallEndedAt)   : now();
        $dureeSecondes = max(0, (int) $dateFin->diffInSeconds($dateHeure, false));

        $appel = Appel::create([
            'appelable_type'     => get_class($this->currentContact),
            'appelable_id'       => $this->currentContact->id,
            'user_id'            => Auth::id(),
            'type'               => EventType::Appel,
            'date_heure'         => $dateHeure,
            'duree_secondes'     => $dureeSecondes,
            'resultat'           => $eventResult,
            'commentaire'        => $this->commentaires ?: null,
            'phoning_status'     => $this->statut_resultat,
            'phoning_result'     => $this->getResultLabel(),
            'phoning_notes'      => $this->commentaires ?: null,
            'phoning_completed_at' => $dateFin,
            'phoning_agent_id'   => Auth::id(),
            'campagne_id'        => $this->currentCampagneId,
            'ringover_call_id'   => $this->ringoverCallId,
            'numero_appelant'    => $this->ringoverDialedPhone ?: ($this->currentContactData['telephone'] ?? null),
            'fiche_type'         => $ficheType,
            'fiche_data'         => $ficheType ? $this->buildFicheData($ficheType) : null,
        ]);

        $this->lastAppelId = $appel->id;
    }

    protected function dispatchFicheGenerationJob(): void
    {
        if (empty($this->lastAppelId)) {
            return;
        }

        dispatch(new \App\Jobs\GenerateFicheWordJob($this->lastAppelId));
    }

    protected function getResultLabel(): string
    {
        $statut = $this->getSelectedStatus();

        return $statut ? trim("{$statut->icone} {$statut->label}") : $this->statut_resultat;
    }
}
