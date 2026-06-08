<?php

namespace App\Filament\NsConseil\Pages;

use App\Models\ArtisanProspection;
use App\Models\ContactPartenaire;
use App\Models\ContactParticulier;
use App\Models\Prospect;
use App\Models\ScriptAppel;
use App\Models\User;
use App\Enums\StatutCampagneProspection;
use App\Enums\ProspectStatut;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class PhoningWorkflow extends Page
{
    protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel   = 'Campagne d\'appels';
    protected static ?string $navigationGroup   = 'Activités';
    protected static ?int    $navigationSort    = 2;
    protected static string  $view              = 'filament.ns-conseil.pages.phoning-workflow';

    public ?Model  $currentContact     = null;
    public string  $contactType        = '';
    public array   $currentContactData = [];

    public string $commentaires    = '';
    public string $statut_resultat = '';
    public string $rappel_date     = '';
    public string $rappel_heure    = '';

    public string $activeScriptTab = 'accroche';

    public int $progress  = 0;
    public int $total     = 0;
    public int $completed = 0;

    public array $scripts = [];

    public ?int  $supervisedUserId = null;
    public bool  $isSupervisorMode = false;
    public array $contactQueue     = [];

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $user = Auth::user();

        $this->isSupervisorMode = $user?->hasAnyRole([
            'super_admin', 'administrateur', 'responsable_plateau', 'superviseur',
        ]) ?? false;

        $this->supervisedUserId = $user?->id;

        $this->loadQueue();
        $this->loadNextContact();
    }

    // ── Requête centrale téléprospecteurs ────────────────────────────
    // Double critère : rôle Spatie OU role_cache pour couvrir les deux cas
    protected function queryTeleprospecteurs()
    {
        return User::where(function ($q) {
                $q->whereHas('roles', fn ($r) => $r->where('name', User::ROLE_TELEPROSPECTEUR))
                  ->orWhere('role_cache', User::ROLE_TELEPROSPECTEUR);
            })
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom');
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
        $userId   = $this->supervisedUserId ?? Auth::id();
        $cacheKey = "phoning_queue_user_{$userId}";
        $ordered  = Cache::get($cacheKey);

        if ($ordered) {
            $this->contactQueue = $this->filterValidQueue($ordered);
            return;
        }

        $this->contactQueue = $this->buildDefaultQueue($userId);
    }

    protected function filterValidQueue(array $queue): array
    {
        return collect($queue)->filter(function ($item) {
            if ($item['type'] === 'prospect') {
                return Prospect::where('id', $item['id'])
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at')
                    ->exists();
            }
            return true;
        })->values()->toArray();
    }

    protected function buildDefaultQueue(int $userId): array
    {
        $queue = [];

        $prospects = Prospect::query()
            ->where('teleprospecteur_id', $userId)
            ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
            ->whereNull('deleted_at')
            ->orderByRaw("CASE
                WHEN statut = 'rpc'       THEN 1
                WHEN statut = 'rp'        THEN 2
                WHEN statut = 'std_joint' THEN 3
                WHEN statut = 'ac'        THEN 4
                WHEN statut = 'std_nr'    THEN 5
                WHEN statut = 'cse_nr'    THEN 6
                ELSE 7 END")
            ->orderBy('rappel_planifie_at', 'asc')
            ->get();

        foreach ($prospects as $p) {
            $queue[] = ['type' => 'prospect', 'id' => $p->id];
        }

        $artisans = ArtisanProspection::query()
            ->where('teleprospecteur_id', $userId)
            ->whereIn('statut_campagne', [
                StatutCampagneProspection::AC->value,
                StatutCampagneProspection::NR->value,
                StatutCampagneProspection::OBJ->value,
            ])
            ->get();

        foreach ($artisans as $a) {
            $queue[] = ['type' => 'artisan', 'id' => $a->id];
        }

        return $queue;
    }

    // ── Prochain contact ──────────────────────────────────────────────
    public function loadNextContact(): void
    {
        if (empty($this->contactQueue)) {
            $this->currentContact     = null;
            $this->currentContactData = [];
            $this->scripts            = [];
            $this->total              = 0;
            $this->progress           = 0;

            Notification::make()
                ->title('🎉 File vide !')
                ->body('Aucun contact à appeler pour le moment.')
                ->success()
                ->send();
            return;
        }

        $this->total    = count($this->contactQueue);
        $this->progress = $this->total > 0
            ? round(($this->completed / $this->total) * 100)
            : 0;

        $next  = $this->contactQueue[0];
        $model = $this->resolveModel($next['type'], $next['id']);

        if (! $model) {
            array_shift($this->contactQueue);
            $this->loadNextContact();
            return;
        }

        $this->currentContact     = $model;
        $this->contactType        = $next['type'];
        $this->currentContactData = $this->buildContactData($model, $next['type']);
        $this->loadScripts();

        $this->reset(['commentaires', 'statut_resultat', 'rappel_date', 'rappel_heure']);
        $this->activeScriptTab = 'accroche';
    }

    protected function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'prospect'    => Prospect::find($id),
            'artisan'     => ArtisanProspection::find($id),
            'partenaire'  => ContactPartenaire::find($id),
            'particulier' => ContactParticulier::find($id),
            default       => null,
        };
    }

    protected function buildContactData(Model $model, string $type): array
    {
        return match ($type) {
            'prospect' => [
                'nom'              => $model->nom,
                'prenom'           => null,
                'siret'            => $model->siret,
                'type_pressenti'   => $model->type_pressenti_label,
                'secteur_activite' => $model->secteur_activite,
                'nb_salaries'      => $model->nb_salaries,
                'chiffre_affaires' => $model->chiffre_affaires
                    ? number_format($model->chiffre_affaires, 0, ',', ' ') . ' €'
                    : null,
                'telephone'        => $model->telephone,
                'telephone_alt'    => $model->telephone_alt,
                'email'            => $model->email,
                'adresse'          => $model->adresse,
                'ville'            => $model->ville,
                'code_postal'      => $model->code_postal,
                'departement'      => $model->departement,
                'adresse_complete' => $model->adresse_complete,
                'interlocuteur_nom'       => $model->interlocuteur_nom,
                'interlocuteur_fonction'  => $model->interlocuteur_fonction,
                'interlocuteur_telephone' => $model->interlocuteur_telephone,
                'interlocuteur_email'     => $model->interlocuteur_email,
                'interlocuteur'           => $model->interlocuteur_complet,
                'statut'                  => $model->statut_label,
                'statut_color'            => $model->statut_color,
                'statut_description'      => $model->statut_description,
                'taux_engagement'         => $model->taux_engagement,
                'priorite'                => $model->type_pressenti
                    ? ucfirst(str_replace('_', ' ', $model->type_pressenti))
                    : 'Standard',
                'teleprospecteur'  => $model->teleprospecteur
                    ? trim("{$model->teleprospecteur->prenom} {$model->teleprospecteur->nom}")
                    : null,
                'commercial'       => $model->commercial
                    ? trim("{$model->commercial->prenom} {$model->commercial->nom}")
                    : null,
                'date_premier_contact' => $model->date_premier_contact?->format('d/m/Y'),
                'rappel_planifie_at'   => $model->rappel_planifie_at?->format('d/m/Y à H:i'),
                'rappel_en_retard'     => $model->rappel_est_en_retard,
                'jours_depuis_contact' => $model->jours_depuis_premier_contact,
                'notes'                => $model->description,
                'motif_ko'             => $model->motif_ko,
                'qf_valide'            => $model->qf_valide,
                'id'                   => $model->id,
                'type'                 => 'prospect',
            ],
            'artisan' => [
                'nom'            => $model->nom,
                'prenom'         => null,
                'telephone'      => $model->telephone,
                'telephone_alt'  => null,
                'email'          => null,
                'statut'         => $model->statut_campagne->label(),
                'statut_color'   => 'info',
                'priorite'       => $model->priorite_segment->label(),
                'metier'         => $model->corps_de_metier?->label(),
                'notes'          => $model->notes,
                'id'             => $model->id,
                'type'           => 'artisan',
                'adresse_complete'=> null,
                'interlocuteur'  => null,
            ],
            'partenaire' => [
                'nom'           => $model->nom,
                'prenom'        => $model->prenom,
                'telephone'     => $model->telephone_direct ?? $model->telephone_mobile ?? $model->telephone_perso,
                'telephone_alt' => null,
                'email'         => $model->email ?? $model->email_perso,
                'statut'        => $model->est_principal ? 'Principal' : 'Contact',
                'statut_color'  => 'success',
                'priorite'      => $model->niveau_influence_label ?? 'Standard',
                'notes'         => $model->notes,
                'id'            => $model->id,
                'type'          => 'partenaire',
                'interlocuteur' => $model->fonction,
                'adresse_complete' => null,
            ],
            'particulier' => [
                'nom'            => $model->nom,
                'prenom'         => $model->prenom,
                'telephone'      => $model->telephone,
                'telephone_alt'  => null,
                'email'          => $model->email,
                'statut'         => $model->statut_occupant?->label() ?? 'Contact',
                'statut_color'   => 'gray',
                'priorite'       => $model->type_logement?->label() ?? 'Standard',
                'notes'          => $model->adresse_complete,
                'id'             => $model->id,
                'type'           => 'particulier',
                'adresse_complete'=> $model->adresse_complete ?? null,
                'interlocuteur'  => null,
            ],
            default => [],
        };
    }

    protected function loadScripts(): void
    {
        $this->scripts = ScriptAppel::parOngletPourContact($this->contactType);
    }

    public function getScriptCourant(): ?ScriptAppel
    {
        return $this->scripts[$this->activeScriptTab] ?? null;
    }

    public function getVariablesScript(): array
    {
        $d = $this->currentContactData;
        return [
            'contact_nom'    => $d['nom']    ?? '',
            'contact_prenom' => $d['prenom'] ?? '',
            'commercial_nom' => Auth::user()?->name ?? '[VOTRE NOM]',
        ];
    }

    // ── Appel ─────────────────────────────────────────────────────────
    public function callNow(): void
    {
        $phoneNumber = $this->currentContactData['telephone'] ?? null;
        if (! $phoneNumber) {
            Notification::make()->title('Numéro manquant')->danger()->send();
            return;
        }
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        $this->redirect("https://phone.aircall.io/call/{$phoneNumber}");
    }

    // ── Enregistrement ────────────────────────────────────────────────
    public function submitResult(): void
    {
        if (! $this->currentContact) return;

        $this->validate([
            'statut_resultat' => 'required|in:std_nr,std_joint,cse_nr,rp,rpc,ko',
            'commentaires'    => 'nullable|string|max:2000',
        ]);

        match ($this->contactType) {
            'artisan'     => $this->updateArtisan(),
            'partenaire'  => $this->updatePartenaire(),
            'particulier' => $this->updateParticulier(),
            'prospect'    => $this->updateProspect(),
            default       => null,
        };

        Notification::make()
            ->title('Contact enregistré')
            ->body('Statut : ' . $this->getResultLabel())
            ->success()
            ->send();

        array_shift($this->contactQueue);
        $this->completed++;
        $this->loadNextContact();
    }

    protected function updateArtisan(): void
    {
        $artisan = $this->currentContact;
        $nouveauStatut = match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => StatutCampagneProspection::RP,
            'std_nr', 'cse_nr'        => StatutCampagneProspection::NR,
            'ko'                      => StatutCampagneProspection::KO ?? StatutCampagneProspection::NR,
            default                   => StatutCampagneProspection::AC,
        };
        $artisan->changerStatut($nouveauStatut, $this->commentaires);
        $artisan->marquerContact();
        if ($this->statut_resultat === 'rp' && $this->rappel_date) {
            $artisan->ajouterNote("Rappel programmé le {$this->rappel_date}" . ($this->rappel_heure ? " {$this->rappel_heure}" : ''));
        }
    }

    protected function updatePartenaire(): void
    {
        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] ";
        $note .= match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => "✅ Contact joint",
            'std_nr', 'cse_nr'        => "❌ Non joignable",
            'ko'                      => "🚫 Refus / KO",
            default                   => "Appel effectué",
        };
        if ($this->commentaires) $note .= "\n{$this->commentaires}";
        $this->currentContact->ajouterNote($note);
    }

    protected function updateParticulier(): void
    {
        $note = "[Appel du " . now()->format('d/m/Y H:i') . "] ";
        $note .= match ($this->statut_resultat) {
            'std_joint', 'rp', 'rpc' => "✅ Joint",
            'std_nr', 'cse_nr'        => "❌ Non joignable",
            'ko'                      => "🚫 KO",
            default                   => "Appel",
        };
        if ($this->commentaires) $note .= " - {$this->commentaires}";
        $this->currentContact->update([
            'notes' => ($this->currentContact->notes ? $this->currentContact->notes . "\n" : '') . $note,
        ]);
    }

    protected function updateProspect(): void
    {
        $prospect = $this->currentContact;
        $nouveauStatut = match ($this->statut_resultat) {
            'rp'        => ProspectStatut::RP,
            'rpc'       => ProspectStatut::RPC,
            'std_joint' => ProspectStatut::STD_Joint,
            'std_nr'    => ProspectStatut::STD_NR,
            'cse_nr'    => ProspectStatut::CSE_NR,
            'ko'        => ProspectStatut::KO,
            default     => ProspectStatut::AC,
        };
        $note = match ($this->statut_resultat) {
            'rp'        => "✅ Réponse positive",
            'rpc'       => "✅ Réponse positive CSE",
            'std_joint' => "📞 Standard joint",
            'std_nr'    => "❌ Standard non référencé",
            'cse_nr'    => "❌ CSE non référencé",
            'ko'        => "🚫 KO - Refus",
            default     => "Appel effectué",
        };
        if ($this->commentaires) $note .= " — {$this->commentaires}";

        if ($nouveauStatut === ProspectStatut::KO) {
            $prospect->marquerKO($note);
        } else {
            $prospect->changerStatut($nouveauStatut, $note);
        }
        $prospect->marquerContact();

        if (in_array($this->statut_resultat, ['rp', 'rpc']) && $this->rappel_date) {
            try {
                $fmt = 'Y-m-d' . ($this->rappel_heure ? ' H:i' : '');
                $val = $this->rappel_date . ($this->rappel_heure ? ' ' . $this->rappel_heure : '');
                $dt  = \DateTime::createFromFormat($fmt, $val);
                if ($dt) $prospect->programmerRappel($dt);
            } catch (\Exception) {}
        }
    }

    // ── Passer ────────────────────────────────────────────────────────
    public function skipCall(): void
    {
        if (empty($this->contactQueue)) return;
        $first = array_shift($this->contactQueue);
        $this->contactQueue[] = $first;
        Notification::make()->title('Contact passé')->body('Repoussé en fin de file.')->warning()->send();
        $this->loadNextContact();
    }

    protected function getResultLabel(): string
    {
        return match ($this->statut_resultat) {
            'std_nr'    => '❌ STD-NR',
            'std_joint' => '📞 STD-Joint',
            'cse_nr'    => '🟠 CSE-NR',
            'rp'        => '✅ RP – Rappel planifié',
            'rpc'       => '⭐ RPC – RDV à planifier',
            'ko'        => '🚫 KO',
            default     => $this->statut_resultat,
        };
    }

    // ── Données pour la vue ───────────────────────────────────────────
    public function getTeleprospecteurs(): array
    {
        return $this->queryTeleprospecteurs()
            ->get()
            ->map(fn ($u) => [
                'id'           => $u->id,
                'nom_complet'  => trim("{$u->prenom} {$u->nom}"),
                'initiales'    => $u->initiales,
                'nb_prospects' => Prospect::where('teleprospecteur_id', $u->id)
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at')
                    ->count(),
            ])
            ->toArray();
    }

    public function getContactInfo(): array
    {
        return $this->currentContactData;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Rafraîchir')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->loadQueue();
                    $this->loadNextContact();
                }),

            Action::make('back_office')
                ->label('Prioriser la file')
                ->icon('heroicon-o-queue-list')
                ->color('warning')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-back-office')),
        ];
    }
}
