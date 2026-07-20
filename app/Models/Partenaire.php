<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Partenaire extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @deprecated Utiliser OrganizationStatus::pourSelect() — conservé pour compat
     * avec le code existant, mais dérivé de l'enum pour ne plus diverger de lui
     * (ex. l'ancien statut "inactif" n'existait pas dans OrganizationStatus et
     * faisait planter le cast enum du modèle si sélectionné).
     */
    public const STATUTS = [
        'a_prospecter' => 'À prospecter',
        'en_cours_prospection' => 'En cours de prospection',
        'rdv_en_cours' => 'RDV en cours',
        'signe_accord_cadre' => 'Signé accord cadre',
        'convention_engagement' => 'Convention d\'engagement',
        'refus' => 'Refus',
    ];

    protected $fillable = [
        // ── Identité ─────────────────────────────────────────────────
        'nom',
        'entreprise',
        'nom_retenu',
        'siret',
        'type',
        'nomenclature_interne',
        'entreprise_mere_id',
        // ── Localisation ─────────────────────────────────────────────
        'adresse',
        'code_postal',
        'ville',
        'departement',
        'telephone',
        'email',
        // ── Activité ─────────────────────────────────────────────────
        'secteur_activite',
        'nb_salaries',
        'chiffre_affaires',
        // ── Statut / Suivi ────────────────────────────────────────────
        'statut',
        'date_modification_statut',
        'date_signature',       // ✅ Ajout MEA
        'annee_signature',
        // ── Fonctionnement partenariat ────────────────────────────────
        'possibilite_permanence',
        'replicable',
        // ✅ Ajout MEA : flag parrainage entreprise (OUI/NON)
        'parrainage_entreprise',
        // ✅ Ajout MEA : syndicat majoritaire au sein du CSE
        'syndicat_majoritaire',
        // ── Origine / Parrainage ──────────────────────────────────────
        'origine_contact',
        'parrain_marraine',
        'parrain_marraine_texte', // ✅ Ajout MEA : texte libre du parrain/marraine
        // ── Clés étrangères ───────────────────────────────────────────
        'entite_id',             // ✅ Ajout MEA : FK vers ENTITE_COMMERCIALE
        'entreprise_id',         // FK vers ENTREPRISE
        'commercial_id',
        'conseiller_id',
        'parrain_partenaire_id',
        'prospect_id',
        // ── Misc ──────────────────────────────────────────────────────
        'nombre_ventes_liees',
        'notes',
        'commentaire_import',
        'date_evaluation',
        'statut_prospection',
        'commentaires',          // ✅ Ajout MEA : champ commentaires du MEA
    ];

    protected $casts = [
        'type' => OrganizationType::class,
        'statut' => OrganizationStatus::class,
        'date_signature' => 'date',
        'date_modification_statut' => 'datetime',
        'date_evaluation' => 'date',
        'parrainage_entreprise' => 'boolean', // ✅ OUI/NON → bool
        'nb_salaries' => 'integer',
        'chiffre_affaires' => 'decimal:2',
        'nombre_ventes_liees' => 'integer',
        'annee_signature' => 'integer',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────

    public function getStatutLabelAttribute(): string
    {
        return $this->statut->label();
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut->color();
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type->value;
    }

    public function getNomCompletAttribute(): string
    {
        return $this->nom.' ('.$this->type->value.')';
    }

    /**
     * Nomenclature du « Nom retenu » : « [Entreprise] [Ville] [Département] [Type] ».
     */
    public static function genererNomenclature($type, ?string $entreprise, ?string $ville, ?string $departement = null): string
    {
        $typeLabel = $type instanceof OrganizationType
            ? $type->value
            : (OrganizationType::tryFrom((string) $type)?->value ?? (string) $type);

        return collect([$entreprise, $ville, $departement, $typeLabel])
            ->filter(fn ($part) => filled($part))
            ->map(fn ($part) => trim((string) $part))
            ->implode(' ');
    }

    public function getNomenclatureSuggereeAttribute(): string
    {
        return self::genererNomenclature($this->type ?: OrganizationType::CSE, $this->entreprise ?: $this->nom, $this->ville, $this->departement);
    }

    public function synchroniserNomenclatureInterne(): void
    {
        $nomenclature = $this->nomenclature_suggeree;

        if (filled($nomenclature)) {
            $this->nomenclature_interne = $nomenclature;
        }
    }

    /**
     * @var array<string, array<int, static>>|null
     */
    private static ?array $nomenclatureIndex = null;

    /**
     * Retrouve un partenaire à partir d'un nom importé (nomenclature libre,
     * ex: "Stellantis Valenciennes CSE") — utilisé pour rattacher un Client
     * importé à son Partenaire correspondant.
     *
     * 1) Correspondance exacte sur nomenclature_interne / nom / nom_retenu
     *    (rapide, comportement historique).
     * 2) À défaut, correspondance tolérante (accents, casse, espaces,
     *    numéro de département inséré/absent) — n'est retenue que si elle
     *    désigne un seul partenaire sans ambiguïté, pour éviter un mauvais
     *    rattachement (ex: même enseigne dans plusieurs départements).
     */
    public static function resolveByNomenclature(string $nomenclature): ?self
    {
        $nomenclature = trim($nomenclature);
        if ($nomenclature === '') {
            return null;
        }

        $exact = static::query()
            ->where('nomenclature_interne', $nomenclature)
            ->orWhere('nom', $nomenclature)
            ->orWhere('nom_retenu', $nomenclature)
            ->first();

        if ($exact) {
            return $exact;
        }

        $candidats = static::nomenclatureIndex()[static::normaliserNomenclature($nomenclature)] ?? [];

        // Dédoublonner par id : un même partenaire peut apparaître deux fois
        // sous la même clé (ex. nom_retenu et nomenclature_interne identiques),
        // ce qui ne doit pas être compté comme une ambiguïté entre 2 partenaires.
        $partenaires = collect($candidats)->unique('id');

        return $partenaires->count() === 1 ? $partenaires->first() : null;
    }

    /**
     * Invalide l'index en mémoire (utile après création/modification de
     * partenaires dans le même run, ex: import en cours).
     */
    public static function oublierIndexNomenclature(): void
    {
        self::$nomenclatureIndex = null;
    }

    /**
     * @return array<string, array<int, static>>
     */
    protected static function nomenclatureIndex(): array
    {
        if (self::$nomenclatureIndex !== null) {
            return self::$nomenclatureIndex;
        }

        $index = [];

        static::query()
            ->select(['id', 'nom', 'nom_retenu', 'nomenclature_interne', 'entreprise', 'type'])
            ->get()
            ->each(function (self $partenaire) use (&$index) {
                $candidats = [$partenaire->nom, $partenaire->nom_retenu, $partenaire->nomenclature_interne];

                // Nomenclature courte "[Entreprise] [Type]", sans ville ni
                // département : certaines sources (ex. imports clients) ne
                // renseignent que ces deux informations et ne mentionnent
                // jamais la ville, ce que la clé complète ci-dessus ne peut
                // pas matcher.
                if ($partenaire->type instanceof OrganizationType) {
                    $candidats[] = trim(($partenaire->entreprise ?: $partenaire->nom).' '.$partenaire->type->value);
                }

                foreach (array_filter($candidats) as $candidat) {
                    $cle = static::normaliserNomenclature($candidat);
                    if ($cle === '') {
                        continue;
                    }
                    $index[$cle][] = $partenaire;
                }
            });

        return self::$nomenclatureIndex = $index;
    }

    /**
     * Normalise un nom de partenaire pour comparaison tolérante : majuscules,
     * sans accents, sans ponctuation, sans nombres isolés (souvent un numéro
     * de département inséré/absent selon la source), espaces réduits.
     */
    protected static function normaliserNomenclature(?string $valeur): string
    {
        if (! $valeur) {
            return '';
        }

        $valeur = Str::ascii($valeur);
        $valeur = mb_strtoupper($valeur);
        $valeur = preg_replace('/\b\d{1,3}\b/', ' ', $valeur);
        $valeur = preg_replace('/[^A-Z0-9]+/', ' ', $valeur);

        return trim(preg_replace('/\s+/', ' ', $valeur));
    }

    public function getAdresseCompleteAttribute(): string
    {
        return trim($this->adresse.', '.$this->code_postal.' '.$this->ville);
    }

    // ── Scopes ──────────────────────────────────────────────────────

    public function scopeByType($query, OrganizationType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatut($query, OrganizationStatus $statut)
    {
        return $query->where('statut', $statut);
    }

    public function scopeActifs($query)
    {
        return $query->whereIn('statut', [
            OrganizationStatus::AProspecter,
            OrganizationStatus::EnCoursProspection,
            OrganizationStatus::RdvEnCours,
        ]);
    }

    public function scopeConventionnes($query)
    {
        return $query->whereIn('statut', [
            OrganizationStatus::SigneAccordCadre,
            OrganizationStatus::ConventionEngagement,
        ]);
    }

    public function scopeARelancer($query, int $joursSansContact = 30)
    {
        return $query->whereIn('statut', [
            OrganizationStatus::AProspecter,
            OrganizationStatus::EnCoursProspection,
        ])->where(function ($q) use ($joursSansContact) {
            $q->whereNull('date_modification_statut')
                ->orWhere('date_modification_statut', '<=', now()->subDays($joursSansContact));
        });
    }

    public function scopeByEntite($query, int $entiteId)
    {
        return $query->where('entite_id', $entiteId);
    }

    // ── Méthodes métier ─────────────────────────────────────────────

    public function estActif(): bool
    {
        return ! in_array($this->statut, [
            OrganizationStatus::Refus,
            OrganizationStatus::ConventionEngagement,
        ]);
    }

    public function estCSE(): bool
    {
        return $this->type === OrganizationType::CSE;
    }

    public function estSyndicat(): bool
    {
        return $this->type === OrganizationType::Syndicat;
    }

    public function estEntrepriseDirecte(): bool
    {
        return $this->type === OrganizationType::EntrepriseDirecte;
    }

    public function estAssociation(): bool
    {
        return $this->type === OrganizationType::Association;
    }

    public function changerStatut(OrganizationStatus $nouveauStatut): void
    {
        $this->update([
            'statut' => $nouveauStatut,
            'date_modification_statut' => now(),
        ]);
    }

    public function signerAccordCadre(): void
    {
        $this->changerStatut(OrganizationStatus::SigneAccordCadre);
    }

    public function signerConvention(): void
    {
        $this->update([
            'statut' => OrganizationStatus::ConventionEngagement,
            'date_signature' => $this->date_signature ?? now(),
            'date_modification_statut' => now(),
        ]);
    }

    public function refuser(?string $motif = null): void
    {
        $this->update([
            'statut' => OrganizationStatus::Refus,
            'date_modification_statut' => now(),
            'notes' => $motif ? $this->notes."\nRefus: ".$motif : $this->notes,
        ]);
    }

    // ── Boot ────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saving(function (Partenaire $partenaire) {
            $partenaire->synchroniserNomenclatureInterne();

            if (blank($partenaire->nom_retenu)) {
                $partenaire->nom_retenu = $partenaire->nomenclature_suggeree;
            }
        });

        static::updating(function (Partenaire $partenaire) {
            if ($partenaire->isDirty('statut')) {
                $partenaire->date_modification_statut = now();

                if ($partenaire->statut === OrganizationStatus::ConventionEngagement && ! $partenaire->date_signature) {
                    $partenaire->date_signature = now();
                }
            }
        });

        static::creating(function (Partenaire $partenaire) {
            if (! $partenaire->statut) {
                $partenaire->statut = OrganizationStatus::AProspecter;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────

    public function entite()
    {
        return $this->belongsTo(EntiteCommerciale::class, 'entite_id');
    }

    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class, 'entreprise_id');
    }

    public function entrepriseMere()
    {
        return $this->belongsTo(Partenaire::class, 'entreprise_mere_id');
    }

    public function filiales()
    {
        return $this->hasMany(Partenaire::class, 'entreprise_mere_id');
    }

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function conseiller()
    {
        return $this->belongsTo(Consultant::class, 'conseiller_id');
    }

    public function parrainPartenaire()
    {
        return $this->belongsTo(Partenaire::class, 'parrain_partenaire_id');
    }

    public function filleuls()
    {
        return $this->hasMany(Partenaire::class, 'parrain_partenaire_id');
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class)->withTrashed();
    }

    public function sentEmails(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\SentEmail::class, 'emailable');
    }

    public function contacts()
    {
        return $this->hasMany(ContactPartenaire::class);
    }

    public function adresseCse()
    {
        return $this->hasOne(AdresseCse::class);
    }

    public function tarification()
    {
        return $this->hasOne(Tarification::class);
    }

    // ✅ Relations MEA ajoutées ────────────────────────────────────────

    public function historiqueConseillers()
    {
        return $this->hasMany(HistoriqueConseiller::class);
    }

    public function autresInterlocuteurs()
    {
        return $this->hasMany(AutresInterlocuteurs::class);
    }

    public function activiteVente()
    {
        return $this->hasOne(ActiviteVente::class);
    }

    public function activitePermanence()
    {
        return $this->hasOne(ActivitePermanence::class);
    }

    public function remboursementEmployeur()
    {
        return $this->hasOne(RemboursementEmployeur::class);
    }

    // ✅ Relations existantes conservées ──────────────────────────────

    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }

    public function documents()
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }

    public function personnes()
    {
        return $this->hasMany(Client::class, 'partenaire_id');
    }

    public function historiqueInteractions()
    {
        return $this->morphMany(HistoriqueInteractionUser::class, 'interactable');
    }
}
