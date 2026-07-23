<?php

namespace App\Filament\NsConseil\Pages;

use App\Enums\EventResult;
use App\Enums\EventType;
use App\Enums\ProspectStatut;
use App\Enums\StatutCampagneProspection;
use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Models\Appel;
use App\Models\CampagnePhoning;
use App\Models\Prospect;
use App\Models\StatutPhoning;
use App\Models\User;
use App\Services\Aopia\FicheGenerationService;
use App\Services\Crm\CrmProfileService;
use App\Services\Crm\CrmSettingsService;
use App\Services\Phoning\PhoningContactResolver;
use App\Services\Phoning\PhoningContactSearchService;
use App\Services\Phoning\PhoningQueueBuilder;
use App\Services\ProspectionMailService;
use App\Support\CsePhoningWorkflow;
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
    // protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel = 'Flux de travail téléphonique';

    protected static ?string $title = 'Flux de travail téléphonique';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 3;

    public string $searchQuery = '';

    public array $searchResults = [];

    public bool $showSearchResults = false;

    public ?int $selectedContactId = null;

    public string $selectedContactType = '';
    

    // protected static ?int    $navigationSort    = 2;
    protected static string  $view              = 'filament.ns-conseil.pages.phoning-workflow';
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Page accessible via URL pour les boutons de lancement d'appels
    }

    public ?Model $currentContact = null;

    public string $contactType = '';

    public array $currentContactData = [];

    public string $commentaires = '';

    public string $statut_resultat = '';

    public string $rappel_date = '';

    public string $rappel_heure = '';

    // ── Champs Interlocuteur Standard (prospect) ──────────────────────
    public string $nom_interlocuteur_standard = '';

    public string $creneaux_permanence_cse = '';

    public string $email_general_standard = '';

    // ── Champs Interlocuteur CSE (prospect) ──────────────────────────
    public string $interlocuteur_nom = '';

    public string $interlocuteur_fonction = '';

    public string $interlocuteur_telephone = '';

    public string $interlocuteur_email = '';

    // ── Fiche Bleue (RDV confirmé) ───────────────────────────────────
    public string $lieu_rdv = '';

    public bool $invitation_agenda_envoyee = false;

    public bool $enregistrement_appel_joint = false;

    public string $enregistrement_raison = '';

    public string $besoins_exprimes = '';

    public string $objections_soulevees = '';

    public string $points_attention_rdv = '';

    // ── Fiche Verte (RDV à conclure) ─────────────────────────────────
    public string $presence_cse = '';

    public string $jour_dispo_appel = '';

    public int $progress = 0;

    public int $total = 0;

    public int $completed = 0;

    public ?int $supervisedUserId = null;

    public bool $isSupervisorMode = false;

    public ?int $lastAppelId = null;

    public array $contactQueue = [];

    public ?int $currentCampagneId = null;

    /**
     * Filtre de campagne explicitement choisi par l'utilisateur (via "Choisir
     * une campagne" ou le paramètre d'URL), distinct de $currentCampagneId qui
     * lui reflète la campagne d'origine du contact affiché à l'instant (et
     * change à chaque appel en mode "toutes les campagnes" mélangées).
     */
    public ?int $campagneFiltreId = null;

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
        $this->loadNextContact();
    }
    public function updatedSearchQuery(): void
    {
        $this->searchContacts();
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

    /**
     * Recherche des contacts par nom, téléphone, SIRET, etc.
     */
    /**
     * Recherche des contacts par nom, téléphone, SIRET, etc.
     */
    public function searchContacts(): void
    {
        if (strlen($this->searchQuery) < 2) {
            $this->searchResults = [];
            $this->showSearchResults = false;
            return;
        }

        $this->searchResults = app(PhoningContactSearchService::class)->search($this->searchQuery);
        $this->showSearchResults = true;
    }

    /**
     * Sélectionne un contact trouvé par la recherche
     */
    public function selectSearchResult(int $id, string $type): void
    {
        $this->selectedContactId = $id;
        $this->selectedContactType = $type;
        $this->showSearchResults = false;
        $this->searchQuery = '';

        // Charger le contact sélectionné
        $model = $this->resolveModel($type, $id);
        if (!$model) {
            Notification::make()
                ->title('Contact introuvable')
                ->danger()
                ->send();
            return;
        }

        // Ajouter le contact en tête de file
        $contactItem = [
            'id' => $id,
            'type' => $type,
            'campagne_id' => $this->currentCampagneId,
        ];

        // Vérifier si le contact est déjà dans la file
        $exists = collect($this->contactQueue)->contains(function ($item) use ($id, $type) {
            return $item['id'] === $id && $item['type'] === $type;
        });

        if ($exists) {
            // Déplacer en tête de file
            $this->contactQueue = collect($this->contactQueue)
                ->reject(fn($item) => $item['id'] === $id && $item['type'] === $type)
                ->prepend($contactItem)
                ->values()
                ->toArray();
        } else {
            // Ajouter en tête de file
            array_unshift($this->contactQueue, $contactItem);
        }

        Notification::make()
            ->title('Contact sélectionné')
            ->body("{$model->nom} ajouté en tête de file")
            ->success()
            ->send();

        $this->loadNextContact();
    }

    /**
     * Réinitialise la recherche
     */
    public function clearSearch(): void
    {
        $this->searchQuery = '';
        $this->searchResults = [];
        $this->showSearchResults = false;
        $this->selectedContactId = null;
        $this->selectedContactType = '';
    }

    // ── Supervision ───────────────────────────────────────────────────
    public function selectSupervisedUser(int $userId): void
    {
        $this->supervisedUserId = $userId;
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();
    }

    public function resetToSelf(): void
    {
        $this->supervisedUserId = Auth::id();
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();
    }

    // ── File d'appels ─────────────────────────────────────────────────
    public function loadQueue(): void
    {
        $userId = $this->supervisedUserId ?? Auth::id();
        $cacheKey = "phoning_queue_user_{$userId}";
        $ordered = Cache::get($cacheKey);

        if ($ordered) {
            $this->contactQueue = $this->prioriserFile($this->filterValidQueue($ordered));

            return;
        }

        $this->contactQueue = $this->buildDefaultQueue($userId);
        $this->contactQueue = $this->prioriserFile($this->contactQueue);
    }

    protected function filterValidQueue(array $queue): array
    {
        return app(PhoningQueueBuilder::class)->filterValidQueue($queue);
    }

    /**
     * Action déclenchée par les boutons "Rafraîchir" de la vue (barre de
     * recherche et écran "File vide") : recharge la file puis recalcule le
     * prochain contact en un seul aller-retour Livewire.
     */
    public function refreshQueue(): void
    {
        $this->loadQueue();
        $this->loadNextContact();
    }

    protected function buildDefaultQueue(int $userId): array
    {
        return app(PhoningQueueBuilder::class)->buildDefaultQueue($userId, $this->campagneFiltreId);
    }

    /**
     * RAPL-ELU et rappels en retard passent en tête de file (workflow v2).
     */
    protected function prioriserFile(array $queue): array
    {
        return app(PhoningQueueBuilder::class)->prioriserFile($queue);
    }

    // ── Prochain contact ──────────────────────────────────────────────
    public function loadNextContact(): void
    {
        if (empty($this->contactQueue)) {
            $this->currentContact = null;
            $this->currentContactData = [];
            $this->total = 0;
            $this->progress = 0;

            Notification::make()
                ->title('🎉 File vide !')
                ->body('Aucun contact à appeler pour le moment.')
                ->success()
                ->send();

            return;
        }

        $this->total = count($this->contactQueue);
        $this->progress = $this->total > 0
            ? round(($this->completed / $this->total) * 100)
            : 0;

        $next = $this->contactQueue[0];
        $model = $this->resolveModel($next['type'], $next['id']);

        if (! $model) {
            array_shift($this->contactQueue);
            $this->loadNextContact();

            return;
        }

        $this->currentContact = $model;
        $this->contactType = $next['type'];
        $this->currentCampagneId = $next['campagne_id'] ?? null;
        $this->currentContactData = $this->buildContactData($model, $next['type']);

        $this->reset([
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
            // Fiche Bleue
            'lieu_rdv',
            'invitation_agenda_envoyee',
            'enregistrement_appel_joint',
            'enregistrement_raison',
            'besoins_exprimes',
            'objections_soulevees',
            'points_attention_rdv',
            // Fiche Verte
            'presence_cse',
            'jour_dispo_appel',
        ]);

        // Pre-fill prospect interlocutor fields from the loaded model
        if ($next['type'] === 'prospect' && $model instanceof Prospect) {
            $this->nom_interlocuteur_standard = $model->nom_interlocuteur_standard ?? '';
            $this->creneaux_permanence_cse = $model->creneaux_permanence_cse ?? '';
            $this->email_general_standard = $model->email_general_standard ?? '';
            $this->interlocuteur_nom = $model->interlocuteur_nom ?? '';
            $this->interlocuteur_fonction = $model->interlocuteur_fonction ?? '';
            $this->interlocuteur_telephone = $model->interlocuteur_telephone ?? '';
            $this->interlocuteur_email = $model->interlocuteur_email ?? '';
        }
    }

    protected function resolveModel(string $type, int $id): ?Model
    {
        return app(PhoningContactResolver::class)->resolveModel($type, $id);
    }

    protected function buildContactData(Model $model, string $type): array
    {
        return app(PhoningContactResolver::class)->buildContactData($model, $type);
    }

    // ── Appel ─────────────────────────────────────────────────────────
    // ── Appel ─────────────────────────────────────────────────────────
    public function callNow(): void
    {
        $phoneNumber = $this->currentContactData['telephone'] ?? null;
        if (! $phoneNumber) {
            Notification::make()->title('Numéro manquant')->danger()->send();

            return;
        }
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        // On ne redirige plus toute la page : on envoie le numéro au badge
        // Ringover flottant (déjà persistant sur tout le site) via un événement
        // browser, écouté dans phoning-workflow.blade.php.
        $this->dispatch('ringover-call', phone: $phoneNumber);
    }

    // ── Enregistrement ────────────────────────────────────────────────
    public function submitResult(): void
    {
        if (! $this->currentContact) {
            return;
        }

        $codesValides = StatutPhoning::forModelType($this->contactType)
            ->pluck('code')
            ->implode(',');

        if (empty($codesValides)) {
            $codesValides = $this->contactType === 'client'
                ? 'std_nr,rp,ko'
                : 'nrp,fax,supp,maj,rdv,cse_ni,rapl_elu,rapl_std,bloc,bloc2,ncse_50,ncse_plus50,cse_zone,cse_hz';
        }

        $this->validate([
            'statut_resultat' => 'required|in:' . $codesValides,
            'commentaires' => $this->commentaireRequis() ? 'required|string|min:5|max:2000' : 'nullable|string|max:2000',
            'interlocuteur_email' => 'nullable|email',
            'email_general_standard' => 'nullable|email',
        ], [
            'commentaires.required' => $this->messageCommentaireObligatoire(),
        ]);

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
            $updateData['interlocuteur_nom'] = $this->interlocuteur_nom;
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
        if (! empty($updateData)) {
            $prospect->update($updateData);
        }

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->marquerKO($note);
        } else {
            $prospect->changerStatut($nouveauStatut, $note);
        }
        $prospect->marquerContact();
        $prospect->assignerTeleprospecteur(Auth::id());


        // ── Envoi du mail correspondant au statut ──────────────────────
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
            ['rdv' => $rdv]
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

    protected function commentaireRequis(): bool
    {
        if (blank($this->statut_resultat)) {
            return false;
        }

        $statut = StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        return (bool) ($statut?->note_obligatoire);
    }

    protected function messageCommentaireObligatoire(): string
    {
        $statut = StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        if ($statut?->message_note_obligatoire) {
            return 'Note obligatoire : ' . $statut->message_note_obligatoire;
        }

        return 'Un commentaire est obligatoire pour ce statut.';
    }

    public function compterTentativesNonAbouties(?Model $contact = null): int
    {
        $contact = $contact ?? $this->currentContact;
        if (! $contact) {
            return 0;
        }

        $codes = StatutPhoning::where('model_type', 'prospect')
            ->where('compte_comme_tentative', true)
            ->pluck('code')
            ->toArray();

        if (empty($codes)) {
            $codes = ['nrp', 'fax', 'std_nr'];
        }

        return Appel::where('appelable_type', get_class($contact))
            ->where('appelable_id', $contact->id)
            ->whereIn('phoning_status', $codes)
            ->count();
    }

    // ── Fiches récap ──────────────────────────────────────────────────
    protected function determineFicheType(): ?string
    {
        $statut = StatutPhoning::where('model_type', $this->contactType ?: 'prospect')
            ->where('code', $this->statut_resultat)
            ->first();

        return $statut?->fiche_type;
    }

    protected function buildFicheData(string $ficheType): array
    {
        $info = $this->currentContactData;
        $prospect = $this->currentContact;

        $base = [
            'raison_sociale' => $info['nom'] ?? null,
            'secteur_activite' => $info['secteur_activite'] ?? null,
            'effectif_total' => $info['nb_salaries'] ?? null,
            'adresse' => $info['adresse_complete'] ?? null,
            'interlocuteur_nom' => $this->interlocuteur_nom ?: ($info['interlocuteur_nom'] ?? null),
            'interlocuteur_fonction' => $this->interlocuteur_fonction ?: ($info['interlocuteur_fonction'] ?? null),
            'interlocuteur_telephone' => $this->interlocuteur_telephone ?: ($info['interlocuteur_telephone'] ?? null),
            'interlocuteur_email' => $this->interlocuteur_email ?: ($info['interlocuteur_email'] ?? null),
            'teleprospecteur_id' => Auth::id(),
            'commercial_id' => $prospect?->commercial_id ?? null,
            'date_appel' => now()->format('d/m/Y'),
        ];

        return match ($ficheType) {
            'bleue' => array_merge($base, [
                'date_rdv' => $this->rappel_date ?: null,
                'heure_rdv' => $this->rappel_heure ?: null,
                'lieu_rdv' => $this->lieu_rdv ?: null,
                'invitation_agenda_envoyee' => $this->invitation_agenda_envoyee,
                'enregistrement_appel_joint' => $this->enregistrement_appel_joint,
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

        $appel = Appel::create([
            'appelable_type' => get_class($this->currentContact),
            'appelable_id' => $this->currentContact->id,
            'user_id' => Auth::id(),
            'type' => EventType::Appel,
            'date_heure' => now(),
            'resultat' => $eventResult,
            'commentaire' => $this->commentaires ?: null,
            'phoning_status' => $this->statut_resultat,
            'phoning_result' => $this->getResultLabel(),
            'phoning_notes' => $this->commentaires ?: null,
            'phoning_completed_at' => now(),
            'phoning_agent_id' => Auth::id(),
            'campagne_id' => $this->currentCampagneId,
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

    public function getCallHistory(): array
    {
        if (! $this->currentContact) {
            return [];
        }

        return Appel::where('appelable_type', get_class($this->currentContact))
            ->where('appelable_id', $this->currentContact->id)
            ->with('user')
            ->orderBy('date_heure', 'desc')
            ->limit(15)
            ->get()
            ->map(fn($a) => [
                'date' => $a->date_heure->format('d/m/Y H:i'),
                'agent' => $a->user ? trim("{$a->user->prenom} {$a->user->nom}") : 'Système',
                'statut' => $a->phoning_status ?? $a->resultat?->value,
                'statut_label' => $a->phoning_result ?? $a->resultat?->label() ?? '—',
                'notes' => $a->phoning_notes ?? $a->commentaire,
                'campagne' => $a->campagne?->nom,
            ])
            ->toArray();
    }

    // ── Passer ────────────────────────────────────────────────────────
    public function skipCall(): void
    {
        if (empty($this->contactQueue)) {
            return;
        }
        $first = array_shift($this->contactQueue);
        $this->contactQueue[] = $first;
        Notification::make()->title('Contact passé')->body('Repoussé en fin de file.')->warning()->send();
        $this->loadNextContact();
    }

    protected function getResultLabel(): string
    {
        $statut = StatutPhoning::where('model_type', $this->contactType)
            ->where('code', $this->statut_resultat)
            ->first();

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
        return $this->currentContactData;
    }

    public function getStatutsPhoning(): array
    {
        $type = $this->contactType ?: 'prospect';

        return StatutPhoning::forModelType($type)
            ->map(fn($s) => [
                'value' => $s->code,
                'label' => $s->label,
                'sub' => $s->description,
                'action' => $s->action_immediate,
                'couleur' => $s->couleur,
                'bar' => $s->couleur_css,
                'icon' => $s->icone,
                'note_obligatoire' => $s->note_obligatoire,
                'prioritaire' => $s->prioritaire,
                'fiche_type' => $s->fiche_type,
                'groupe' => $s->groupe,
                'groupe_label' => $s->groupe_label,
            ])
            ->toArray();
    }

    /**
     * Statuts prospect groupés par cas (workflow CSE v2).
     *
     * @return array<string, array{label: string, statuts: list<array>}>
     */
    public function getStatutsPhoningGroupes(): array
    {
        if (($this->contactType ?: 'prospect') !== 'prospect') {
            return ['default' => ['label' => 'Résultats', 'statuts' => $this->getStatutsPhoning()]];
        }

        return CsePhoningWorkflow::statutsGroupesPourProspect();
    }

    public function getTentativesAppel(): int
    {
        return $this->compterTentativesNonAbouties();
    }
    public function selectCampagne(int $campagneId): void
    {
        $this->currentCampagneId = $campagneId;
        $this->campagneFiltreId = $campagneId;
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();

        $campagne = CampagnePhoning::find($campagneId);
        Notification::make()
            ->title('Campagne sélectionnée')
            ->body($campagne?->nom ?? 'Campagne #' . $campagneId)
            ->success()
            ->send();
    }

    public function clearCampagne(): void
    {
        $this->currentCampagneId = null;
        $this->campagneFiltreId = null;
        $this->completed = 0;
        $this->loadQueue();
        $this->loadNextContact();

        Notification::make()
            ->title('Toutes les campagnes')
            ->body('File rechargée avec toutes les campagnes actives.')
            ->info()
            ->send();
    }

    public function getCampagneInfo(): ?array
    {
        if (! $this->currentCampagneId) {
            return null;
        }

        $campagne = CampagnePhoning::find($this->currentCampagneId);
        if (! $campagne) {
            return null;
        }

        $stats = $campagne->getStats();

        return [
            'id' => $campagne->id,
            'nom' => $campagne->nom,
            'statut' => $campagne->statut,
            'statut_label' => $campagne->statut_label,
            'type_entite' => $campagne->type_entite_label,
            'total_contacts' => $stats['total_contacts'],
            'contacts_traites' => $stats['contacts_traites'],
            'progression' => $stats['progression'],
            'total_appels' => $stats['total_appels'],
        ];
    }

    /**
     * Nombre exact de contacts encore appelables (ignore ceux déjà traités
     * définitivement, mais garde les "non répondu" et assimilés puisqu'ils
     * restent à rappeler) — recalculé en base à chaque affichage plutôt que
     * déduit de la taille de $contactQueue, qui elle diminue de façon
     * irréversible au fil des appels de la session en cours.
     */
    public function getContactsRestantsCount(): int
    {
        if ($this->campagneFiltreId) {
            return CampagnePhoning::find($this->campagneFiltreId)?->countQueueContacts() ?? 0;
        }

        $userId = $this->supervisedUserId ?? Auth::id();

        return CampagnePhoning::active()
            ->forUser($userId)
            ->get()
            ->sum(fn (CampagnePhoning $campagne) => $campagne->countQueueContacts());
    }

    public function getCampagnesDisponibles(): array
    {
        $userId = $this->supervisedUserId ?? Auth::id();

        return CampagnePhoning::active()
            ->forUser($userId)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'type_entite' => $c->type_entite_label,
                'contacts' => $c->countContacts(),
            ])
            ->toArray();
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
