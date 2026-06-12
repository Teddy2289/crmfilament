<?php

namespace App\Models;

use App\Enums\StatutCampagneProspection;
use App\Enums\PrioriteSegment;
use App\Enums\CorpsDeMetier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ArtisanProspection extends Model
{
    use SoftDeletes;

    protected $table = 'artisan_prospections';

    protected $casts = [
        'statut_campagne' => StatutCampagneProspection::class,
        'priorite_segment' => PrioriteSegment::class,
        'corps_de_metier' => CorpsDeMetier::class,
        'date_dernier_contact' => 'datetime',
        'date_envoi_document' => 'datetime',
        'accord_verbal' => 'boolean',
    ];

    protected $fillable = [
        'nom',
        'corps_de_metier',
        'telephone',
        'zone_geo',
        'statut_campagne',
        'date_dernier_contact',
        'teleprospecteur_id',
        'priorite_segment',
        'accord_verbal',
        'date_envoi_document',
        'artisan_id',
        'notes',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getStatutLabelAttribute(): string
    {
        return $this->statut_campagne->label();
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut_campagne->color();
    }

    public function getStatutIconAttribute(): string
    {
        return $this->statut_campagne->icon();
    }

    public function getPrioriteLabelAttribute(): string
    {
        return $this->priorite_segment->label();
    }

    public function getPrioriteColorAttribute(): string
    {
        return $this->priorite_segment->color();
    }

    public function getMetierLabelAttribute(): string
    {
        return $this->corps_de_metier->label();
    }

    public function getMetierIconAttribute(): string
    {
        return $this->corps_de_metier->icon();
    }

    public function getDelaiRecontactAttribute(): string
    {
        $heures = $this->priorite_segment->delaiRecontactHeures();

        if ($heures < 24) {
            return "{$heures}h";
        }

        $jours = floor($heures / 24);
        return "{$jours} jour(s)";
    }

    public function getProchainContactAttribute(): ?string
    {
        if (!$this->date_dernier_contact) {
            return 'Immédiat';
        }

        $delai = $this->priorite_segment->delaiRecontactHeures();
        $prochain = $this->date_dernier_contact->copy()->addHours($delai);

        if ($prochain->isPast()) {
            return 'En retard depuis ' . $prochain->diffForHumans();
        }

        return $prochain->diffForHumans();
    }

    public function getTauxConversionAttribute(): float
    {
        $total = static::where('teleprospecteur_id', $this->teleprospecteur_id)->count();
        if ($total === 0) return 0;

        $converties = static::where('teleprospecteur_id', $this->teleprospecteur_id)
            ->whereNotNull('artisan_id')
            ->count();

        return round(($converties / $total) * 100, 1);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estActif(): bool
    {
        return $this->statut_campagne->estActif();
    }

    public function estConvertible(): bool  // ✅ Du modèle 2
    {
        return $this->statut_campagne->estConvertible() &&
            $this->accord_verbal &&
            !$this->artisan_id;
    }

    public function estConverti(): bool
    {
        return !is_null($this->artisan_id);
    }

    public function estPrioritaire(): bool
    {
        return $this->priorite_segment === PrioriteSegment::Haute;
    }

    public function doitEtreRelance(): bool  // ✅ Du modèle 2
    {
        if (!$this->statut_campagne->estActif()) {
            return false;
        }

        if (!$this->date_dernier_contact) {
            return true;
        }

        $delaiHeures = $this->priorite_segment->delaiRecontactHeures();
        return $this->date_dernier_contact->addHours($delaiHeures)->isPast();
    }

    public function changerStatut(StatutCampagneProspection $nouveauStatut, ?string $notes = null): void
    {
        if (!in_array($nouveauStatut, $this->statut_campagne->statutsSuivants())) {
            throw new \Exception(
                "Transition impossible de {$this->statut_campagne->value} à {$nouveauStatut->value}"
            );
        }

        $data = [
            'statut_campagne' => $nouveauStatut,
            'date_dernier_contact' => now(),
        ];

        if ($notes) {
            $data['notes'] = $this->notes
                ? $this->notes . "\n[" . now()->format('d/m/Y H:i') . "] {$notes}"
                : $notes;
        }

        $this->update($data);
    }

    public function marquerContact(): void
    {
        $this->update(['date_dernier_contact' => now()]);
    }

    public function donnerAccordVerbal(): void
    {
        $this->update([
            'accord_verbal'        => true,
            'statut_campagne'      => StatutCampagneProspection::SOC,
            'date_dernier_contact' => now(),
        ]);
    }

    public function convertirEnArtisan(): ?Artisan  // ✅ Du modèle 2 (amélioré)
    {
        if (!$this->estConvertible()) {
            return null;
        }

        \DB::beginTransaction();
        try {
            $artisan = Artisan::create([
                'nom' => $this->nom,
                'corps_de_metier' => $this->corps_de_metier,
                'telephone_principal' => $this->telephone,
                'zone_intervention' => $this->zone_geo,
                'date_souscription' => now(),
                'statut_compte' => StatutCompteArtisan::EnAttenteActivation,
                'notes' => "Converti depuis prospection #{$this->id}\n" . ($this->notes ?? ''),
            ]);

            $this->update([
                'artisan_id' => $artisan->id,
                'statut_campagne' => StatutCampagneProspection::SOC,
                'notes' => $this->notes . "\n[Conversion] Artisan créé le " . now()->format('d/m/Y H:i'),
            ]);

            \DB::commit();
            return $artisan;
        } catch (\Exception $e) {
            \DB::rollBack();
            throw $e;
        }
    }

    public function marquerHorsCible(string $motif = null): void
    {
        $this->changerStatut(StatutCampagneProspection::HC, $motif ?? 'Marqué hors cible');
    }

    public function relancer(): void
    {
        if ($this->statut_campagne === StatutCampagneProspection::HC) {
            $this->changerStatut(StatutCampagneProspection::AC, 'Relance depuis HC');
        } else {
            $this->changerStatut($this->statut_campagne, 'Relance effectuée');
        }
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs($query): Builder
    {
        return $query->whereIn('statut_campagne', [
            StatutCampagneProspection::AC->value,
            StatutCampagneProspection::NR->value,
            StatutCampagneProspection::RP->value,
            StatutCampagneProspection::OBJ->value,
        ]);
    }

    public function scopeARelancer($query): Builder  // ✅ Du modèle 2 (amélioré)
    {
        return $query->whereIn('statut_campagne', [
            StatutCampagneProspection::AC->value,
            StatutCampagneProspection::NR->value,
            StatutCampagneProspection::OBJ->value,
        ])->where(function ($q) {
            $q->whereNull('date_dernier_contact')
                ->orWhere(function ($subQ) {
                    // Utilise le délai de la priorité plutôt qu'un fixe de 72h
                    $subQ->where('priorite_segment', PrioriteSegment::Haute->value)
                        ->where('date_dernier_contact', '<=', now()->subHours(24))
                        ->orWhere('priorite_segment', PrioriteSegment::Standard->value)
                        ->where('date_dernier_contact', '<=', now()->subHours(72))
                        ->orWhere('priorite_segment', PrioriteSegment::Basse->value)
                        ->where('date_dernier_contact', '<=', now()->subHours(168));
                });
        });
    }

    public function scopeConvertibles($query): Builder
    {
        return $query->where('statut_campagne', StatutCampagneProspection::SOC)
            ->where('accord_verbal', true)
            ->whereNull('artisan_id');
    }

    public function scopeConverties($query): Builder
    {
        return $query->whereNotNull('artisan_id');
    }

    public function scopeNonConverties($query): Builder
    {
        return $query->whereNull('artisan_id');
    }

    public function scopePrioritaires($query): Builder
    {
        return $query->where('priorite_segment', PrioriteSegment::Haute);
    }

    public function scopeByMetier($query, CorpsDeMetier $metier): Builder
    {
        return $query->where('corps_de_metier', $metier);
    }

    public function scopeByTeleprospecteur($query, int $userId): Builder
    {
        return $query->where('teleprospecteur_id', $userId);
    }

    public function scopeSansContactDepuis($query, int $jours): Builder
    {
        return $query->where('date_dernier_contact', '<', now()->subDays($jours))
            ->orWhereNull('date_dernier_contact');
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(?int $teleprospecteurId = null): array
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        return [
            'total' => $query->count(),
            'actifs' => (clone $query)->actifs()->count(),
            'a_relancer' => (clone $query)->aRelancer()->count(),
            'convertibles' => (clone $query)->convertibles()->count(),
            'converties' => (clone $query)->converties()->count(),
            'taux_conversion' => static::getTauxConversionGlobal($teleprospecteurId),
            'par_statut' => static::getRepartitionParStatut($teleprospecteurId),
            'par_metier' => static::getRepartitionParMetier($teleprospecteurId),
        ];
    }

    public static function getTauxConversionGlobal(?int $teleprospecteurId = null): float
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        $total = $query->count();
        if ($total === 0) return 0;

        $converties = (clone $query)->whereNotNull('artisan_id')->count();
        return round(($converties / $total) * 100, 1);
    }

    public static function getRepartitionParStatut(?int $teleprospecteurId = null): array
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        return collect(StatutCampagneProspection::cases())
            ->mapWithKeys(function ($statut) use ($query) {
                return [$statut->value => (clone $query)->where('statut_campagne', $statut)->count()];
            })
            ->toArray();
    }

    public static function getRepartitionParMetier(?int $teleprospecteurId = null): array
    {
        $query = static::query();

        if ($teleprospecteurId) {
            $query->where('teleprospecteur_id', $teleprospecteurId);
        }

        return (clone $query)
            ->selectRaw('corps_de_metier, COUNT(*) as total')
            ->groupBy('corps_de_metier')
            ->orderByDesc('total')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->corps_de_metier->value => $item->total];
            })
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (ArtisanProspection $prospection) {
            // ✅ Du modèle 2 : Priorité auto selon métier
            if ($prospection->corps_de_metier && !$prospection->priorite_segment) {
                $prospection->priorite_segment = PrioriteSegment::depuisCorpsDeMetier(
                    $prospection->corps_de_metier
                );
            }

            // Statut par défaut
            if (!$prospection->statut_campagne) {
                $prospection->statut_campagne = StatutCampagneProspection::AC;
            }
        });

        static::updating(function (ArtisanProspection $prospection) {
            // ✅ Du modèle 2 : Date envoi document si accord verbal
            if (
                $prospection->isDirty('accord_verbal') &&
                $prospection->accord_verbal &&
                !$prospection->date_envoi_document
            ) {
                $prospection->date_envoi_document = now();
            }

            // Mettre à jour date_dernier_contact si changement de statut
            if (
                $prospection->isDirty('statut_campagne') &&
                !$prospection->isDirty('date_dernier_contact')
            ) {
                $prospection->date_dernier_contact = now();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function teleprospecteur()
    {
        return $this->belongsTo(User::class, 'teleprospecteur_id');
    }

    public function artisan()
    {
        return $this->belongsTo(Artisan::class);
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }
}
