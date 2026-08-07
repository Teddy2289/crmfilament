<?php

namespace App\Models;

use App\Enums\ProspectStatut;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampagnePhoning extends Model
{
    use SoftDeletes;

    protected $table = 'campagne_phonings';

    protected $fillable = [
        'nom',
        'description',
        'statut',
        'type_entite',
        'criteres',
        'max_tentatives',
        'jours_refroidissement',
        'exclure_autres_campagnes',
        'exclure_sans_telephone',
        'script_appel',
        'date_debut',
        'date_fin',
        'user_id',
        'groupe_telepro_id',
        'entite_id',
    ];

    protected $casts = [
        'criteres' => 'array',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'max_tentatives' => 'integer',
        'jours_refroidissement' => 'integer',
        'exclure_autres_campagnes' => 'boolean',
        'exclure_sans_telephone' => 'boolean',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'planifiee' => 'Planifiée',
        'active' => 'Active (En cours)',
        'en_pause' => 'En Pause',
        'terminee' => 'Terminée',
    ];

    public const TYPES_ENTITE = [
        'prospects' => 'Prospects',
        'partenaires' => 'Partenaires',
        'clients' => 'Clients',
    ];

    // ── Relations ────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function groupeTelepro()
    {
        return $this->belongsTo(GroupeTelepro::class, 'groupe_telepro_id');
    }

    public function entite()
    {
        return $this->belongsTo(EntiteCommerciale::class, 'entite_id');
    }

    public function appels()
    {
        return $this->hasMany(Appel::class, 'campagne_id');
    }

    // ── Statistiques campagne ─────────────────────────────────────────

    public function getStats(): array
    {
        $totalContacts = $this->countContacts();
        $totalAppels = $this->appels()->count();

        // Un contact ne compte comme "traité" que s'il a été réellement joint
        // au moins une fois (un appel dont le statut n'est pas marqué
        // "compte_comme_tentative" = simple tentative infructueuse, ex :
        // NRP, FAX, sans réponse...). Un contact uniquement joint via des
        // statuts de ce type reste donc "restant" (à rappeler).
        $codesNonAboutis = StatutPhoning::where('model_type', $this->queueContactType())
            ->where('compte_comme_tentative', true)
            ->pluck('code');

        $contactsTraites = $this->appels()
            ->when(
                $codesNonAboutis->isNotEmpty(),
                fn (Builder $q) => $q->whereNotIn('phoning_status', $codesNonAboutis)
            )
            ->distinct('appelable_id')
            ->count('appelable_id');

        $contactsRestants = max(0, $totalContacts - $contactsTraites);

        $parStatut = $this->appels()
            ->selectRaw('phoning_status, COUNT(*) as total')
            ->groupBy('phoning_status')
            ->pluck('total', 'phoning_status')
            ->toArray();

        $progression = $totalContacts > 0
            ? round(($contactsTraites / $totalContacts) * 100, 1)
            : 0;

        return [
            'total_contacts' => $totalContacts,
            'contacts_traites' => $contactsTraites,
            'contacts_restants' => $contactsRestants,
            'total_appels' => $totalAppels,
            'progression' => $progression,
            'par_statut' => $parStatut,
        ];
    }

    /**
     * Codes de statut phoning effectivement rencontrés lors de cette campagne,
     * ordonnés selon l'ordre de configuration des statuts.
     */
    public function statutsUtilises(): array
    {
        $codes = $this->appels()
            ->whereNotNull('phoning_status')
            ->distinct()
            ->pluck('phoning_status');

        if ($codes->isEmpty()) {
            return [];
        }

        $ordreConnu = StatutPhoning::where('model_type', $this->queueContactType())
            ->whereIn('code', $codes)
            ->orderBy('ordre')
            ->pluck('code');

        // Les codes sans définition StatutPhoning (legacy) sont ajoutés à la fin.
        return $ordreConnu->merge($codes->diff($ordreConnu))->values()->all();
    }

    public function statutLabel(string $code): string
    {
        $statut = StatutPhoning::where('model_type', $this->queueContactType())
            ->where('code', $code)
            ->first();

        return $statut?->label ?? $code;
    }

    public function statutCouleur(string $code): string
    {
        $statut = StatutPhoning::where('model_type', $this->queueContactType())
            ->where('code', $code)
            ->first();

        return $statut?->couleur_filament ?? 'gray';
    }

    /**
     * Liste des appels de la campagne pour un statut donné, avec le contact
     * lié chargé — sert de base à la "fiche" de chaque prospect traité.
     *
     * @param  string       $code
     * @param  string|null  $dateDebut  Format YYYY-MM-DD (inclusif)
     * @param  string|null  $dateFin    Format YYYY-MM-DD (inclusif, jusqu'à 23:59:59)
     */
    public function appelsParStatut(string $code, ?string $dateDebut = null, ?string $dateFin = null)
    {
        return $this->appels()
            ->where('phoning_status', $code)
            ->when($dateDebut, fn (Builder $q) => $q->whereDate('date_heure', '>=', $dateDebut))
            ->when($dateFin,   fn (Builder $q) => $q->whereDate('date_heure', '<=', $dateFin))
            ->with(['appelable', 'user'])
            ->orderByDesc('date_heure')
            ->get();
    }

    public function estTerminee(): bool
    {
        $totalContacts = $this->countContacts();
        if ($totalContacts === 0) {
            return false;
        }

        $contactsTraites = $this->appels()
            ->distinct('appelable_id')
            ->count('appelable_id');

        return $contactsTraites >= $totalContacts;
    }

    // ── Scopes ───────────────────────────────────────────────────────

    /**
     * Une campagne est éligible au phoning tant que son statut est "active" —
     * activable/désactivable à tout moment, sans dépendre de date_debut/date_fin
     * (ce sont de simples indications de planning, pas des verrous).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('statut', 'active');
    }

    /**
     * Visible si : ouverte à tous (user_id et groupe_telepro_id null),
     * assignée directement à l'utilisateur, ou assignée à l'un de ses groupes
     * (un téléprospecteur peut appartenir à plusieurs groupes).
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        $groupeIds = User::find($userId)?->groupesTelepro()->pluck('groupes_telepro.id') ?? collect();

        return $query->where(function ($q) use ($userId, $groupeIds) {
            $q->where(fn ($q2) => $q2->whereNull('user_id')->whereNull('groupe_telepro_id'))
                ->orWhere('user_id', $userId)
                ->when($groupeIds->isNotEmpty(), fn ($q2) => $q2->orWhereIn('groupe_telepro_id', $groupeIds));
        });
    }

    // ── Accesseurs ───────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function getStatutColorAttribute(): string
    {
        return match ($this->statut) {
            'active' => 'success',
            'planifiee' => 'info',
            'en_pause' => 'warning',
            'terminee' => 'gray',
            default => 'danger',
        };
    }

    public function getTypeEntiteLabelAttribute(): string
    {
        return self::TYPES_ENTITE[$this->type_entite] ?? $this->type_entite;
    }

    // ── Requête de contacts ──────────────────────────────────────────

    /**
     * Retourne la requête Eloquent filtrée selon les critères de la campagne.
     */
    public function buildQuery(): Builder
    {
        $criteres = $this->criteres ?? [];

        return match ($this->type_entite) {
            'prospects' => $this->buildProspectsQuery($criteres),
            'partenaires' => $this->buildPartenairesQuery($criteres),
            'clients' => $this->buildClientsQuery($criteres),
            default => throw new \InvalidArgumentException("type_entite inconnu : {$this->type_entite}"),
        };
    }

    /**
     * Retourne les IDs des contacts de la campagne sous forme de tableau
     * [['type' => '...', 'id' => ..., 'campagne_id' => ...], ...]
     */
    public function getContactsQueue(): array
    {
        return $this->buildQueueQuery()
            ->pluck('id')
            ->map(fn ($id) => ['type' => $this->queueContactType(), 'id' => $id, 'campagne_id' => $this->id])
            ->toArray();
    }

    public function countContacts(): int
    {
        return $this->buildQuery()->count();
    }

    public function countQueueContacts(): int
    {
        return $this->buildQueueQuery()->count();
    }

    public function queueContactType(): string
    {
        return match ($this->type_entite) {
            'partenaires' => 'partenaire',
            'clients' => 'client',
            default => 'prospect',
        };
    }

    /**
     * Requête des contacts encore appelables dans la file de la campagne.
     */
    public function buildQueueQuery(): Builder
    {
        $retireCodes = StatutPhoning::query()
            ->where('model_type', $this->queueContactType())
            ->where('retire_de_file', true)
            ->pluck('code')
            ->all();

        $query = $this->buildQuery();

        if ($retireCodes !== []) {
            $query->whereDoesntHave(
                'appels',
                fn (Builder $appelQuery) => $appelQuery
                    ->where('campagne_id', $this->id)
                    ->whereIn('phoning_status', $retireCodes)
            );
        }

        return match ($this->type_entite) {
            'prospects' => $this->applyProspectQueueFilters($query)
                ->with(['teleprospecteur', 'commercial']),
            'partenaires' => $query->with('partenaire'),
            'clients' => $query->with(['partenaire', 'commercial']),
            default => $query,
        };
    }

    // ── Constructeurs de requêtes par entité ─────────────────────────

    protected function applyProspectQueueFilters(Builder $query): Builder
    {
        $retireCodes = StatutPhoning::query()
            ->where('model_type', 'prospect')
            ->where('retire_de_file', true)
            ->pluck('code')
            ->all();

        $query->whereNotIn('statut', [
            ProspectStatut::KO->value,
            ProspectStatut::QF->value,
        ]);

        if ($retireCodes !== []) {
            $query->whereDoesntHave(
                'appels',
                fn (Builder $appelQuery) => $appelQuery
                    ->where('campagne_id', $this->id)
                    ->whereIn('phoning_status', $retireCodes)
            );
        }

        return $query;
    }

    protected function buildProspectsQuery(array $c): Builder
    {
        $q = Prospect::query()->whereNull('deleted_at');

        // Règle 1: Exclusion si téléphone absent / non renseigné
        if ($this->exclure_sans_telephone ?? true) {
            $q->where(function ($sub) {
                $sub->whereNotNull('telephone')
                    ->where('telephone', '!=', '')
                    ->orWhere(function ($s2) {
                        $s2->whereNotNull('telephone_alt')->where('telephone_alt', '!=', '');
                    });
            });
        }

        // Règle 2: Période de refroidissement (refroidissement depuis le dernier appel)
        if (! empty($this->jours_refroidissement) && $this->jours_refroidissement > 0) {
            $dateLimite = now()->subDays((int) $this->jours_refroidissement);
            $q->whereDoesntHave('appels', function (Builder $aQuery) use ($dateLimite) {
                $aQuery->where('date_heure', '>=', $dateLimite);
            });
        }

        // Règle 4: Nombre maximal de tentatives d'appel non abouties
        // Utilise une sous-requête WHERE plutôt que HAVING sur un alias pour
        // assurer la compatibilité SQLite (tests) et MySQL (production).
        if (! empty($this->max_tentatives) && $this->max_tentatives > 0) {
            $max = (int) $this->max_tentatives;
            $q->whereRaw(
                '(SELECT COUNT(*) FROM appels WHERE appels.appelable_id = prospects.id AND appels.appelable_type = ? AND appels.compte_comme_tentative = 1) < ?',
                [Prospect::class, $max]
            );
        }

        if (is_array($c['statuts'] ?? null) && count($c['statuts']) > 0) {
            $q->whereIn('statut', $c['statuts']);
        }

        // Filtres de dates : Rappel planifié
        if (! empty($c['rappel_date_debut'])) {
            $q->where('rappel_planifie_at', '>=', $c['rappel_date_debut']);
        }
        if (! empty($c['rappel_date_fin'])) {
            $q->where('rappel_planifie_at', '<=', $c['rappel_date_fin'] . ' 23:59:59');
        }

        // Filtres de dates : Rendez-vous prévus
        if (! empty($c['rdv_date_debut']) || ! empty($c['rdv_date_fin'])) {
            $q->whereHas('rendezVous', function (Builder $rdvQuery) use ($c) {
                if (! empty($c['rdv_date_debut'])) {
                    $rdvQuery->where('date_heure', '>=', $c['rdv_date_debut']);
                }
                if (! empty($c['rdv_date_fin'])) {
                    $rdvQuery->where('date_heure', '<=', $c['rdv_date_fin'] . ' 23:59:59');
                }
            });
        }

        if (! empty($c['departement'])) {
            $q->where('departement', $c['departement']);
        }
        if (! empty($c['secteur_activite'])) {
            $q->where('secteur_activite', 'like', '%'.$c['secteur_activite'].'%');
        }
        if (isset($c['nb_salaries_min']) && $c['nb_salaries_min'] !== '') {
            $q->where('nb_salaries', '>=', (int) $c['nb_salaries_min']);
        }
        if (isset($c['nb_salaries_max']) && $c['nb_salaries_max'] !== '') {
            $q->where('nb_salaries', '<=', (int) $c['nb_salaries_max']);
        }
        if (! empty($c['type_pressenti'])) {
            $q->where('type_pressenti', $c['type_pressenti']);
        }

        return $q;
    }

    protected function buildPartenairesQuery(array $c): Builder
    {
        // On charge les ContactPartenaire dont le Partenaire parent correspond aux critères,
        // pour rester compatible avec le type 'partenaire' du PhoningWorkflow.
        return ContactPartenaire::query()
            ->whereNull('deleted_at')
            ->whereHas('partenaire', function ($q) use ($c) {
                $q->whereNull('deleted_at');
                if (is_array($c['statuts'] ?? null) && count($c['statuts']) > 0) {
                    $q->whereIn('statut', $c['statuts']);
                }
                if (! empty($c['departement'])) {
                    $q->where('departement', $c['departement']);
                }
                if (! empty($c['type'])) {
                    $q->where('type', $c['type']);
                }
                if (! empty($c['secteur_activite'])) {
                    $q->where('secteur_activite', 'like', '%'.$c['secteur_activite'].'%');
                }
            });
    }

    protected function buildClientsQuery(array $c): Builder
    {
        $retireCodes = StatutPhoning::query()
            ->where('model_type', 'client')
            ->where('retire_de_file', true)
            ->pluck('code')
            ->all();

        $q = Client::query()->whereNull('deleted_at');

        if (! empty($c['etat'])) {
            $q->where('etat', $c['etat']);
        }
        if (! empty($c['departement'])) {
            $q->where('departement', $c['departement']);
        }
        if (! empty($c['type_tiers'])) {
            $q->where('type_tiers', $c['type_tiers']);
        }
        // Toujours exclure les clients "ne plus contacter"
        $q->where(fn ($sub) => $sub->whereNull('ne_plus_contacter')->orWhere('ne_plus_contacter', false));

        if ($retireCodes !== []) {
            $q->whereDoesntHave(
                'appels',
                fn (Builder $appelQuery) => $appelQuery
                    ->where('campagne_id', $this->id)
                    ->whereIn('phoning_status', $retireCodes)
            );
        }

        return $q;
    }
}
