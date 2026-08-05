<?php

namespace App\Filament\NsConseil\Pages;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\ProspectStatut;
use App\Enums\StatutCampagneProspection;
use App\Filament\NsConseil\Concerns\HasCallSession;
use App\Filament\NsConseil\Concerns\HasContactQueue;
use App\Filament\NsConseil\Concerns\HasEmailPreview;
use App\Filament\NsConseil\Concerns\HasStatusResult;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\PipelineStatut;
use App\Models\Prospect;
use App\Models\RendezVous;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Mail\ConfirmationRdvProspectMail;
use App\Mail\ContactSansCSEMail;
use App\Mail\GenericProspectionMail;
use App\Mail\PreviewableProspectionMail;
use App\Mail\PriseContactBlocMail;
use Illuminate\Mail\Mailable;
use App\Services\Aopia\FicheGenerationService;
use App\Services\Crm\CrmProfileService;
use App\Services\Crm\CrmSettingsService;
use App\Services\Crm\PipelineStatutService;
use App\Services\Phoning\PhoningContactResolver;
use App\Services\Phoning\PhoningContactSearchService;
use App\Services\Phoning\PhoningQueueBuilder;
use App\Services\ProspectionMailService;
use App\Support\CsePhoningWorkflow;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;


class PhoningWorkflow extends Page
{
    use HasContactQueue;
    use HasCallSession;
    use HasStatusResult;
    use HasEmailPreview;

    // protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel = 'Flux de travail téléphonique';

    protected static ?string $title = 'Flux de travail téléphonique';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 3;

    // protected static ?int    $navigationSort    = 2;
    protected static string  $view              = 'filament.ns-conseil.pages.phoning-workflow';
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Page accessible via URL pour les boutons de lancement d'appels
    }

    public string $contactType = '';

    public array $currentContactData = [];

    public ?string $ringoverDialedPhone = null;

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();

        $this->isSupervisorMode = app(CrmProfileService::class)
            ->userHasCapability($user, 'supervisor');

        $this->supervisedUserId = $user?->id;

        // Filtrer sur une campagne spécifique si passée en URL
        if ($campagneId = request()->query('campagne_id')) {
            $this->currentCampagneId = (int) $campagneId;
            $this->campagneFiltreId = (int) $campagneId;
        }

        $this->loadQueue();

        if ($contactId = request()->query('contact_id')) {
            $this->requestedContactId = (int) $contactId;
            $this->requestedContactType = request()->query('contact_type', 'prospect');
        }

        $this->ensureRequestedContactPriority();
        $this->loadNextContact();
    }
    // ── Requête centrale téléprospecteurs ────────────────────────────
    // Double critère : rôle Spatie OU role_cache pour couvrir les deux cas
    protected function queryTeleprospecteurs()
    {
        $roles = app(CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur']);

        return User::where(function ($q) use ($roles) {
            $q->whereHas('roles', fn($r) => $r->whereIn('name', $roles));
            foreach ($roles as $role) {
                $q->orWhere('role_cache', $role);
            }
        })
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    // ── Prochain contact ──────────────────────────────────────────────
    protected function resetContactFormFields(): void
    {
        $this->reset([
            'selectedContactId',
            'selectedContactType',
            'commentaires',
            'statut_resultat',
            'rappel_date',
            'rappel_heure',
            'nom_interlocuteur_standard',
            'creneaux_permanence_cse',
            'email_general_standard',
            'interlocuteur_nom',
            'interlocuteur_fonction',
            'interlocuteur_telephone',
            'interlocuteur_email',
            'interlocuteur_add_nom',
            'interlocuteur_add_fonction',
            'interlocuteur_add_telephone',
            'interlocuteur_add_email',
            'lieu_rdv',
            'invitation_agenda_envoyee',
            'enregistrement_appel_joint',
            'enregistrement_raison',
            'besoins_exprimes',
            'objections_soulevees',
            'points_attention_rdv',
            'presence_cse',
            'jour_dispo_appel',
        ]);
    }

    protected function populateContactFormFields(Model $model, string $type): void
    {
        if ($type !== 'prospect' || ! $model instanceof Prospect) {
            return;
        }

        $this->nom_interlocuteur_standard = $model->nom_interlocuteur_standard ?? '';
        $this->creneaux_permanence_cse = $model->creneaux_permanence_cse ?? '';
        $this->email_general_standard = $model->email_general_standard ?? '';
        $this->interlocuteur_nom = trim((string) ($model->interlocuteur_prenom ? $model->interlocuteur_prenom.' ' : '') . ($model->interlocuteur_nom ?? ''));
        $this->interlocuteur_fonction = $model->interlocuteur_fonction ?? '';
        $this->interlocuteur_telephone = $model->interlocuteur_telephone ?? '';
        $this->interlocuteur_email = $model->interlocuteur_email ?? '';
        $this->interlocuteur_add_nom = $model->interlocuteur_add_nom ?? '';
        $this->interlocuteur_add_fonction = $model->interlocuteur_add_fonction ?? '';
        $this->interlocuteur_add_telephone = $model->interlocuteur_add_telephone ?? '';
        $this->interlocuteur_add_email = $model->interlocuteur_add_email ?? '';
    }

    // ── Appel ─────────────────────────────────────────────────────────

    #[\Livewire\Attributes\On('ringover-call')]
    public function captureRingoverDialedPhone(?string $phone = null): void
    {
        $phone = preg_replace('/[^0-9+]/', '', (string) $phone);
        if (! $phone) {
            return;
        }

        $this->ringoverDialedPhone = $phone;
        $this->incomingCallPhone = $this->incomingCallPhone ?: $phone;
    }

    // ── Enregistrement ────────────────────────────────────────────────
    public function submitResult(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $codesValides = $this->getStatusValidationCodes();

        $this->validate([
            'statut_resultat' => 'required|in:' . implode(',', $codesValides),
            'commentaires' => $this->commentaireRequis() ? 'required|string|min:5|max:2000' : 'nullable|string|max:2000',
            'interlocuteur_email' => 'nullable|email',
            'email_general_standard' => 'nullable|email',
        ], [
            'commentaires.required' => $this->messageCommentaireObligatoire(),
        ]);

        if ($this->shouldPreviewEmail() && ! $this->emailPreviewConfirmed) {
            $this->openEmailPreview();
            return;
        }

        if ($this->showEmailPreview && $this->emailPreviewConfirmed) {
            $this->showEmailPreview = false;
        }

        match ($this->contactType) {
            'artisan' => $this->updateArtisan(),
            'partenaire' => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
            'prospect' => $this->updateProspect(),
            'client' => $this->updateClient(),
            default => null,
        };

        $this->enregistrerAppel();

        // Dispatch job de génération de fiche Word si applicable
        $this->dispatchFicheGenerationJob();

        // Auto-génération des fiches Word liées au statut phoning (système existant)
        if ($this->contactType === 'prospect' && $this->currentContact instanceof Prospect) {
            try {
                $ficheService = app(FicheGenerationService::class);
                $docs = $ficheService->genererAutoParStatut(
                    $this->statut_resultat,
                    $this->currentContact,
                    $this->currentContact->rendezVous()->latest('date_heure')->first()
                );
                if (! empty($docs)) {
                    $noms = collect($docs)->pluck('nom_fichier')->implode(', ');
                    Notification::make()
                        ->title('Fiches générées automatiquement')
                        ->body($noms)
                        ->info()
                        ->send();
                }
            } catch (\Throwable) {
                // Ne pas bloquer le workflow si la génération échoue
            }
        }

        Notification::make()
            ->title('Contact enregistré')
            ->body('Statut : ' . $this->getResultLabel())
            ->success()
            ->send();

        if ($this->currentContact && $this->contactType) {
            app(PhoningQueueBuilder::class)->releaseQueueReservationForUser(
                Auth::id(),
                $this->contactType,
                (int) $this->currentContact->getKey(),
            );
        }

        $this->resetEmailPreviewState();

        array_shift($this->contactQueue);
        $this->completed++;

        $this->checkCampagneCompletion();

        $this->loadNextContact();
    }

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

    protected function updateArtisan(): void
    {
        $artisan = $this->currentContact;
        $nouveauStatut = match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => StatutCampagneProspection::RP,
            'std_nr', 'cse_nr' => StatutCampagneProspection::NR,
            'ko' => StatutCampagneProspection::KO ?? StatutCampagneProspection::NR,
            default => StatutCampagneProspection::AC,
        };
        $artisan->changerStatut($nouveauStatut, $this->commentaires);
        $artisan->marquerContact();
        if ($this->statut_resultat === 'rp' && $this->rappel_date) {
            $artisan->ajouterNote("Rappel programmé le {$this->rappel_date}" . ($this->rappel_heure ? " {$this->rappel_heure}" : ''));
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
            $note .= " - {$this->commentaires}";
        }
        $this->currentContact->update([
            'notes' => ($this->currentContact->notes ? $this->currentContact->notes . "\n" : '') . $note,
        ]);
    }

    protected function updateClient(): void
    {
        $note = '[Appel du ' . now()->format('d/m/Y H:i') . '] ' . $this->getResultLabel();
        if ($this->commentaires) {
            $note .= " — {$this->commentaires}";
        }
        // Stocké dans extra_data car Client n'a pas de champ notes dédié
        $extra = $this->currentContact->extra_data ?? [];
        $extra['historique_appels'][] = $note;
        $this->currentContact->update(['extra_data' => $extra]);
    }

    protected function updateProspect(): void
    {
        $prospect = $this->currentContact;

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
            $note .= " — {$this->commentaires}";
        }

        // Persist interlocutor & standard fields collected during the call
        $updateData = [];
        if ($this->nom_interlocuteur_standard !== '') {
            $updateData['nom_interlocuteur_standard'] = $this->nom_interlocuteur_standard;
        }
        if ($this->creneaux_permanence_cse !== '') {
            $updateData['creneaux_permanence_cse'] = $this->creneaux_permanence_cse;
        }
        if ($this->email_general_standard !== '') {
            $updateData['email_general_standard'] = $this->email_general_standard;
        }
        if ($this->interlocuteur_nom !== '') {
            [$prenom, $nom] = $this->splitFullName($this->interlocuteur_nom);

            if ($prenom !== null) {
                $updateData['interlocuteur_prenom'] = $prenom;
            }
            if ($nom !== null) {
                $updateData['interlocuteur_nom'] = $nom;
            }
        }
        if ($this->interlocuteur_fonction !== '') {
            $updateData['interlocuteur_fonction'] = $this->interlocuteur_fonction;
        }
        if ($this->interlocuteur_telephone !== '') {
            $updateData['interlocuteur_telephone'] = $this->interlocuteur_telephone;
        }
        if ($this->interlocuteur_email !== '') {
            $updateData['interlocuteur_email'] = $this->interlocuteur_email;
        }
        if ($this->interlocuteur_add_nom !== '') {
            $updateData['interlocuteur_add_nom'] = $this->interlocuteur_add_nom;
        }
        if ($this->interlocuteur_add_fonction !== '') {
            $updateData['interlocuteur_add_fonction'] = $this->interlocuteur_add_fonction;
        }
        if ($this->interlocuteur_add_telephone !== '') {
            $updateData['interlocuteur_add_telephone'] = $this->interlocuteur_add_telephone;
        }
        if ($this->interlocuteur_add_email !== '') {
            $updateData['interlocuteur_add_email'] = $this->interlocuteur_add_email;
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

        // ── Envoi du mail correspondant au statut ──────────────────────
        $rdv = null; // déclarée en amont : nécessaire pour TOUS les statuts, pas seulement 'rdv'

        if ($this->statut_resultat === 'rdv') {
            $rdv = $this->creerRendezVous($prospect);
            Log::info("MAIL DEBUG: creerRendezVous", [
                'rappel_date' => $this->rappel_date,
                'rappel_heure' => $this->rappel_heure,
                'rdv_created' => $rdv !== null,
                'rdv_id' => $rdv?->id,
            ]);
        }

        app(ProspectionMailService::class)->envoyerPourStatut(
            $this->statut_resultat,
            $prospect,
            $this->buildProspectionMailContext($rdv)
        );

        // Planifier le rappel selon paramètres back-office
        if ($this->rappel_date) {
            $this->appliquerRappelProspect($prospect);
        } elseif ($statutMeta?->delai_rappel_jours) {
            $prospect->programmerRappel(now()->addDays($statutMeta->delai_rappel_jours));
        } elseif ($statutMeta?->compte_comme_tentative) {
            $max = (int) app(CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
            $tentatives = $this->compterTentativesNonAbouties($prospect) + 1;
            if ($tentatives >= $max) {
                $stdNr = ProspectStatut::tryFrom('STD_NR') ?? ProspectStatut::STD_NR;
                $prospect->changerStatut($stdNr, "{$max} tentatives sans réponse");
                $prospect->marquerDifficile();
                $jours = (int) app(CrmSettingsService::class)->get('prospection.std_nr_reminder_days', 2);
                $prospect->programmerRappel(now()->addDays($jours));
            } else {
                // Fiche encore sous le seuil : nouvelle tentative auto après un délai court
                $heures = (int) app(CrmSettingsService::class)->get('prospection.retry_reminder_hours', 3);
                $prospect->programmerRappel(now()->addHours($heures));
            }
        }
    }

    protected function creerRendezVous(Prospect $prospect): ?\App\Models\RendezVous
    {
        if (! $this->rappel_date) {
            return null; // pas de date saisie, impossible de créer le RDV
        }

        $dateHeure = $this->rappel_date . ' ' . ($this->rappel_heure ?: '09:00');

        return \App\Models\RendezVous::create([
            'rdvable_type' => Prospect::class,
            'rdvable_id' => $prospect->id,
            'date_heure' => $dateHeure,
            'lieu' => $this->lieu_rdv ?: null,
            'statut' => \App\Enums\RendezVousStatut::Planifie,
            'commercial_id' => $prospect->commercial_id,
            'teleprospecteur_id' => Auth::id(),
            'interlocuteur_nom' => $this->interlocuteur_nom ?: $prospect->fallback_interlocuteur_nom,
            'interlocuteur_tel' => $this->interlocuteur_telephone ?: $prospect->fallback_interlocuteur_telephone,
            'interlocuteur_email' => $this->interlocuteur_email ?: $prospect->fallback_interlocuteur_email,
        ]);
    }
    protected function appliquerRappelProspect(Prospect $prospect): void
    {
        try {
            $fmt = 'Y-m-d' . ($this->rappel_heure ? ' H:i' : '');
            $val = $this->rappel_date . ($this->rappel_heure ? ' ' . $this->rappel_heure : '');
            $dt = \DateTime::createFromFormat($fmt, $val);
            if ($dt) {
                $prospect->programmerRappel($dt);
            }
        } catch (\Exception) {
        }
    }

    // ── Fiches récap ──────────────────────────────────────────────────
    protected function determineFicheType(): ?string
    {
        return $this->getSelectedStatus()?->fiche_type;
    }

    protected function buildFicheData(string $ficheType): array
    {
        $info = $this->currentContactData;
        $prospect = $this->currentContact;

        $teleprospecteur = Auth::user();
        $commercial = $prospect instanceof Prospect ? $prospect->commercial : null;

        $base = [
            'raison_sociale' => $info['nom'] ?? null,
            'secteur_activite' => $info['secteur_activite'] ?? null,
            'effectif_total' => $info['nb_salaries'] ?? null,
            'adresse' => $info['adresse_complete'] ?? null,
            'interlocuteur_nom' => $this->interlocuteur_nom ?: ($info['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction' => $this->interlocuteur_fonction ?: ($info['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone' => $this->interlocuteur_telephone ?: ($info['interlocuteur_telephone'] ?? null),
            'interlocuteur_email' => $this->interlocuteur_email ?: ($info['interlocuteur_email'] ?? null),
            'teleprospecteur_nom' => $teleprospecteur ? trim("{$teleprospecteur->prenom} {$teleprospecteur->nom}") : null,
            'commercial_nom' => $commercial ? trim("{$commercial->prenom} {$commercial->nom}") : null,
            'date_appel' => now()->format('d/m/Y'),
        ];

        return match ($ficheType) {
            'bleue' => array_merge($base, [
                'date_rdv' => $this->rappel_date ?: null,
                'heure_rdv' => $this->rappel_heure ?: null,
                'lieu_rdv' => $this->lieu_rdv ?: null,
                'invitation_agenda_envoyee' => $this->invitation_agenda_envoyee ? 'Oui' : 'Non',
                'enregistrement_appel_joint' => $this->enregistrement_appel_joint ? 'Oui' : 'Non',
                'enregistrement_raison' => $this->enregistrement_raison ?: null,
                'besoins_exprimes' => $this->besoins_exprimes ?: null,
                'objections_soulevees' => $this->objections_soulevees ?: null,
                'points_attention_rdv' => $this->points_attention_rdv ?: null,
                'notes_interlocuteur' => $this->commentaires ?: null,
            ]),
            'jaune' => array_merge($base, [
                'commentaires' => $this->commentaires ?: null,
                'date_rappel' => $this->rappel_date ?: now()->addDays(7)->format('Y-m-d'),
                'heure_rappel' => $this->rappel_heure ?: null,
            ]),
            'verte' => array_merge($base, [
                'presence_cse' => $this->presence_cse ?: null,
                'jour_dispo_appel' => $this->jour_dispo_appel ?: null,
                'commentaires' => $this->commentaires ?: null,
                'date_rdv_a_prendre' => $this->rappel_date ?: null,
                'heure_rdv_a_prendre' => $this->rappel_heure ?: null,
            ]),
            default => [],
        };
    }

    // ── Journal d'appel ───────────────────────────────────────────────
    protected function enregistrerAppel(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $eventResult = match ($this->statut_resultat) {
            'nrp', 'fax', 'std_nr', 'cse_nr' => EventResult::NonAbouti,
            'supp', 'cse_hz', 'ko' => EventResult::Annule,
            'rdv', 'rapl_elu', 'rapl_std', 'rp' => EventResult::Rappel,
            default => EventResult::Realise,
        };

        $ficheType = $this->determineFicheType();

        $dateHeure = filled($this->ringoverCallStartedAt)
            ? Carbon::parse($this->ringoverCallStartedAt)
            : now();

        $dateFin = filled($this->ringoverCallEndedAt)
            ? Carbon::parse($this->ringoverCallEndedAt)
            : now();

        $dureeSecondes = max(0, (int) $dateFin->diffInSeconds($dateHeure, false));

        $appel = Appel::create([
            'appelable_type' => get_class($this->currentContact),
            'appelable_id' => $this->currentContact->id,
            'user_id' => Auth::id(),
            'type' => EventType::Appel,
            'date_heure' => $dateHeure,
            'duree_secondes' => $dureeSecondes,
            'resultat' => $eventResult,
            'commentaire' => $this->commentaires ?: null,
            'phoning_status' => $this->statut_resultat,
            'phoning_result' => $this->getResultLabel(),
            'phoning_notes' => $this->commentaires ?: null,
            'phoning_completed_at' => $dateFin,
            'phoning_agent_id' => Auth::id(),
            'campagne_id' => $this->currentCampagneId,
            'ringover_call_id' => $this->ringoverCallId,
            'numero_appelant' => $this->ringoverDialedPhone ?: $this->currentContactData['telephone'] ?? null,
            'fiche_type' => $ficheType,
            'fiche_data' => $ficheType ? $this->buildFicheData($ficheType) : null,
        ]);

        // Store the appel ID for job dispatch
        $this->lastAppelId = $appel->id;
    }

    protected function dispatchFicheGenerationJob(): void
    {
        if (! isset($this->lastAppelId) || ! $this->lastAppelId) {
            return;
        }

        // Dispatch job pour générer la fiche Word depuis le template
        dispatch(new \App\Jobs\GenerateFicheWordJob($this->lastAppelId));
    }

    // ── Passer ────────────────────────────────────────────────────────
    protected function getResultLabel(): string
    {
        $statut = $this->getSelectedStatus();

        if ($statut) {
            return trim("{$statut->icone} {$statut->label}");
        }

        return $this->statut_resultat;
    }

    // ── Données pour la vue ───────────────────────────────────────────
    public function getTeleprospecteurs(): array
    {
        return $this->queryTeleprospecteurs()
            ->withCount([
                'prospectsTeleprospecteur as nb_prospects' => fn ($query) => $query
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at'),
            ])
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'nom_complet' => trim("{$u->prenom} {$u->nom}"),
                'initiales' => $u->initiales,
                'nb_prospects' => $u->nb_prospects,
            ])
            ->toArray();
    }

    public function getContactInfo(): array
    {
        return array_merge($this->currentContactData, [
            'nom_interlocuteur_standard' => $this->nom_interlocuteur_standard ?: ($this->currentContactData['nom_interlocuteur_standard'] ?? null),
            'creneaux_permanence_cse' => $this->creneaux_permanence_cse ?: ($this->currentContactData['creneaux_permanence_cse'] ?? null),
            'email_general_standard' => $this->email_general_standard ?: ($this->currentContactData['email_general_standard'] ?? null),
            'interlocuteur_nom' => $this->interlocuteur_nom ?: ($this->currentContactData['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction' => $this->interlocuteur_fonction ?: ($this->currentContactData['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone' => $this->interlocuteur_telephone ?: ($this->currentContactData['interlocuteur_telephone'] ?? null),
            'interlocuteur_email' => $this->interlocuteur_email ?: ($this->currentContactData['interlocuteur_email'] ?? null),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions principales visibles
            // "Choisir une campagne" / "Toutes les campagnes" changent le
            // périmètre de TOUTE la file (potentiellement celle d'un autre
            // téléprospecteur en mode supervision) : réservé aux superviseurs
            // et admins, un téléprospecteur travaille sa file telle qu'assignée.
            Action::make('choisir_campagne')
                ->label('Choisir une campagne')
                ->icon('heroicon-o-megaphone')
                ->color('primary')
                ->visible(fn () => $this->isSupervisorMode)
                ->form([
                    \Filament\Forms\Components\Select::make('campagne_id')
                        ->label('Campagne')
                        ->options(function () {
                            $userId = $this->supervisedUserId ?? Auth::id();

                            return CampagnePhoning::active()
                                ->forUser($userId)
                                ->get()
                                ->mapWithKeys(fn($c) => [$c->id => "{$c->nom} ({$c->countContacts()} contacts)"]);
                        })
                        ->required()
                        ->searchable(),
                ])
                ->action(fn(array $data) => $this->selectCampagne((int) $data['campagne_id'])),

            Action::make('toutes_campagnes')
                ->label('Toutes les campagnes')
                ->icon('heroicon-o-squares-2x2')
                ->color('secondary')
                ->visible(fn() => $this->isSupervisorMode && $this->currentCampagneId !== null)
                ->action(fn() => $this->clearCampagne()),

            Action::make('refresh')
                ->label('Rafraîchir')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshQueue()),

            // Groupe d'actions dans un dropdown — outils de configuration/
            // pilotage (back-office, paramétrage CSE v2) réservés aux
            // superviseurs/admins, pas au téléprospecteur qui appelle.
            ActionGroup::make([
                Action::make('voir_campagne')
                    ->label('Statistiques campagne')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->visible(fn() => $this->currentCampagneId !== null)
                    ->url(fn() => CampagnePhoningResource::getUrl('view', ['record' => $this->currentCampagneId]))
                    ->openUrlInNewTab(),

                Action::make('workflow_cse')
                    ->label('Parcours CSE v2')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->url(fn() => WorkflowProspectionCse::getUrl())
                    ->openUrlInNewTab(),

                Action::make('statuts_cse')
                    ->label('Statuts CSE v2')
                    ->icon('heroicon-o-tag')
                    ->color('info')
                    ->url(fn() => StatutsAppelsCse::getUrl())
                    ->openUrlInNewTab(),

                Action::make('back_office')
                    ->label('Prioriser la file')
                    ->icon('heroicon-o-queue-list')
                    ->color('warning')
                    ->url(fn() => route('filament.ns-conseil.pages.phoning-back-office')),
            ])
                ->label('Outils')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->visible(fn () => $this->isSupervisorMode)
                ->dropdownPlacement('bottom-end'),
        ];
    }
}
