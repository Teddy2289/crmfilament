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
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'active' => 'Active',
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
     */
    public function appelsParStatut(string $code)
    {
        return $this->appels()
            ->where('phoning_status', $code)
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
     * assignée directement à l'utilisateur, ou assignée à son groupe.
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        $groupeId = User::whereKey($userId)->value('groupe_telepro_id');

        return $query->where(function ($q) use ($userId, $groupeId) {
            $q->where(fn ($q2) => $q2->whereNull('user_id')->whereNull('groupe_telepro_id'))
                ->orWhere('user_id', $userId)
                ->when($groupeId, fn ($q2) => $q2->orWhere('groupe_telepro_id', $groupeId));
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
            'terminee' => 'gray',
            default => 'warning',
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
        $query = $this->buildQuery();

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
                fn (Builder $appelQuery) => $appelQuery->whereIn('phoning_status', $retireCodes)
            );
        }

        return $query;
    }

    protected function buildProspectsQuery(array $c): Builder
    {
        $q = Prospect::query()->whereNull('deleted_at');

        if (is_array($c['statuts'] ?? null) && count($c['statuts']) > 0) {
            $q->whereIn('statut', $c['statuts']);
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

        return $q;
    }
}
