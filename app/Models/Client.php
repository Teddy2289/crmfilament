<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $casts = [
        'date_naissance' => 'date',
        'montant_cpf' => 'decimal:2',
        'ne_plus_contacter' => 'boolean',
        'extra_data' => 'array',
    ];

    protected $fillable = [
        'source_sheet',
        'ref_client',
        'civilite',
        'prenom',
        'nom_tiers',
        'email',
        'telephone',
        'adresse',
        'code_postal',
        'ville',
        'region',
        'departement',
        'date_naissance',
        'entreprise',
        'type_tiers',
        'avis_google',
        'etat',
        'montant_cpf',
        'ne_plus_contacter',
        'partenaire_id',
        'parrain_id',
        'extra_data',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getNomCompletAttribute(): string
    {
        return trim(($this->civilite ? $this->civilite.' ' : '').($this->nom_tiers ?? ''));
    }

    public function getAgeAttribute(): ?int
    {
        return $this->date_naissance?->age;
    }

    public function getAdresseCompleteAttribute(): string
    {
        return collect([$this->adresse, $this->code_postal, $this->ville])
            ->filter()
            ->implode(', ');
    }

    public function getLocalisationAttribute(): string
    {
        return collect([$this->ville, $this->departement, $this->region])
            ->filter()
            ->implode(' - ');
    }

    public function getEstContactableAttribute(): bool
    {
        return ! $this->ne_plus_contacter && ($this->email || $this->telephone);
    }

    public function getInitialesAttribute(): string
    {
        if (! $this->nom_tiers) {
            return '?';
        }

        return collect(explode(' ', $this->nom_tiers))
            ->map(fn ($mot) => strtoupper(substr($mot, 0, 1)))
            ->implode('');
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function marquerNePlusContacter(?string $motif = null): void
    {
        $this->update([
            'ne_plus_contacter' => true,
            'extra_data' => array_merge($this->extra_data ?? [], [
                'motif_npc' => $motif,
                'date_npc' => now()->toDateString(),
            ]),
        ]);
    }

    public function reactiver(): void
    {
        $this->update(['ne_plus_contacter' => false]);
    }

    public function aDesPropositions(): bool
    {
        // Guard : pas de relation si ref_client null
        if (! $this->ref_client) {
            return false;
        }

        return $this->propositions()->exists();
    }

    public function aDesPropositionsEnCours(): bool
    {
        if (! $this->ref_client) {
            return false;
        }

        return $this->propositions()
            ->whereNotIn('etat', ['Terminée', 'Annulée'])
            ->exists();
    }

    public function getTotalHeuresFormation(): int
    {
        if (! $this->ref_client) {
            return 0;
        }

        return (int) $this->propositions()->sum('nb_heures_formation');
    }

    public function getTotalHeuresRealisees(): int
    {
        if (! $this->ref_client) {
            return 0;
        }

        return (int) $this->propositions()->sum('heures_realisees');
    }

    public function getTotalHeuresRestantes(): int
    {
        if (! $this->ref_client) {
            return 0;
        }

        return (int) $this->propositions()->sum('heures_restantes');
    }

    public function getMontantTotalCPF(): float
    {
        $fromPropositions = $this->ref_client
            ? (float) $this->propositions()->sum('montant_cpf')
            : 0.0;

        return $fromPropositions + (float) ($this->montant_cpf ?? 0);
    }

    public function getProgressionFormationAttribute(): float
    {
        $total = $this->getTotalHeuresFormation();
        if ($total === 0) {
            return 0.0;
        }

        return round(($this->getTotalHeuresRealisees() / $total) * 100, 1);
    }

    public function getDernierePropositionAttribute(): ?Proposition
    {
        if (! $this->ref_client) {
            return null;
        }

        return $this->propositions()->latest('date_lancement')->first();
    }

    public function getDerniereFormationAttribute(): ?Proposition
    {
        if (! $this->ref_client) {
            return null;
        }

        return $this->propositions()
            ->whereNotNull('date_debut_formation')
            ->latest('date_debut_formation')
            ->first();
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeContactables(Builder $query): Builder
    {
        return $query->where('ne_plus_contacter', false)
            ->where(function (Builder $q) {
                $q->whereNotNull('email')
                    ->orWhereNotNull('telephone');
            });
    }

    public function scopeNonContactables(Builder $query): Builder
    {
        return $query->where('ne_plus_contacter', true);
    }

    public function scopeAvecPropositions(Builder $query): Builder
    {
        return $query->has('propositions');
    }

    public function scopeSansPropositions(Builder $query): Builder
    {
        return $query->doesntHave('propositions');
    }

    public function scopeAvecCPF(Builder $query): Builder
    {
        return $query->whereNotNull('montant_cpf')->where('montant_cpf', '>', 0);
    }

    public function scopeParRegion(Builder $query, string $region): Builder
    {
        return $query->where('region', $region);
    }

    public function scopeParDepartement(Builder $query, string $departement): Builder
    {
        return $query->where('departement', $departement);
    }

    public function scopeParVille(Builder $query, string $ville): Builder
    {
        return $query->where('ville', 'like', "%{$ville}%");
    }

    public function scopeRecents(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeSansActiviteDepuis(Builder $query, int $jours): Builder
    {
        return $query->whereDoesntHave('propositions', function (Builder $q) use ($jours) {
            $q->where('updated_at', '>=', now()->subDays($jours));
        });
    }

    // ── KPIs statiques ──────────────────────────────────────────────

    public static function getKpis(): array
    {
        return [
            'total' => static::count(),
            'contactables' => static::contactables()->count(),
            'non_contactables' => static::nonContactables()->count(),
            'avec_propositions' => static::avecPropositions()->count(),
            'sans_propositions' => static::sansPropositions()->count(),
            'avec_cpf' => static::avecCPF()->count(),
            'nouveaux_mois' => static::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'par_region' => static::getRepartitionParRegion(),
        ];
    }

    public static function getRepartitionParRegion(): array
    {
        return static::selectRaw('region, COUNT(*) as total')
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->groupBy('region')
            ->orderByDesc('total')
            ->get()
            ->pluck('total', 'region')
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (! $client->ref_client) {
                // Génère une ref unique basée sur timestamp + random pour éviter
                // les collisions sur import massif (plusieurs milliers en parallèle)
                $client->ref_client = 'CLI-'.date('Ymd').'-'.strtoupper(substr(uniqid(), -6));
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function propositions()
    {
        // whereNotNull garantit qu'on ne fait jamais WHERE ref_client = NULL
        return $this->hasMany(Proposition::class, 'ref_client', 'ref_client')
            ->whereNotNull('ref_client');
    }

    public function partenaires()
    {
        return $this->belongsToMany(Partenaire::class);
    }

    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function parrain()
    {
        return $this->belongsTo(Parrain::class);
    }

    public function dossierFormations()
    {
        return $this->hasMany(DossierFormation::class, 'personne_id');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
