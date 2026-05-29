<?php

namespace App\Models;

use App\Enums\TicketStatut;
use App\Enums\NiveauPriorite;
use App\Enums\CorpsDeMetier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'contact_particulier_id',
        'artisan_id',
        'operateur_id',
        'statut',
        'niveau_priorite',
        'corps_de_metier',
        'date_creation',
        'date_cloture',
        'rdv_planifie_at',
        'rappel_promise_at',
        'aircall_call_id',
        'notes',
    ];

    protected $casts = [
        'statut' => TicketStatut::class,
        'niveau_priorite' => NiveauPriorite::class,
        'corps_de_metier' => CorpsDeMetier::class,
        'date_creation' => 'datetime',
        'date_cloture' => 'datetime',
        'rdv_planifie_at' => 'datetime',
        'rappel_promise_at' => 'datetime',
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

    public function getStatutIconAttribute(): string
    {
        return $this->statut->icon();
    }

    public function getPrioriteLabelAttribute(): string
    {
        return $this->niveau_priorite?->label() ?? 'Non défini';
    }

    public function getPrioriteColorAttribute(): string
    {
        return $this->niveau_priorite?->color() ?? 'gray';
    }

    public function getDureeTraitementMinutesAttribute(): int
    {
        if (!$this->date_cloture) {
            return now()->diffInMinutes($this->date_creation);
        }
        return $this->date_creation->diffInMinutes($this->date_cloture);
    }

    public function getDureeTraitementFormateeAttribute(): string
    {
        $minutes = $this->duree_traitement_minutes;

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $heures = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($heures < 24) {
            return $heures . 'h' . ($mins > 0 ? ' ' . $mins . 'min' : '');
        }

        $jours = floor($heures / 24);
        $heures = $heures % 24;
        return $jours . 'j ' . $heures . 'h';
    }

    public function getSlaRespecteAttribute(): bool
    {
        if (!$this->niveau_priorite || !$this->artisan_id) {
            return true;
        }

        $delaiMax = $this->niveau_priorite->delaiMaxMinutes();
        return $this->duree_traitement_minutes <= $delaiMax;
    }

    public function getEstEnRetardAttribute(): bool
    {
        return !$this->sla_respecte && $this->statut->estActif();
    }

    public function getStatutOrdreAttribute(): int
    {
        return $this->statut->ordre();
    }

    public function getProgressionPourcentageAttribute(): int
    {
        return (int) round(($this->statut->ordre() / 14) * 100);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeActifs($query): Builder
    {
        return $query->whereNotIn('statut', [
            TicketStatut::DossierCloture,
            TicketStatut::ClotureSatisfait,
        ]);
    }

    public function scopeClotures($query): Builder
    {
        return $query->whereIn('statut', [
            TicketStatut::DossierCloture,
            TicketStatut::ClotureSatisfait,
        ]);
    }

    public function scopeBloquants($query): Builder
    {
        return $query->whereIn('statut', [
            TicketStatut::FicheIncomplete,
            TicketStatut::ReclamationOuverte,
            TicketStatut::SuiviQualiteRequis,
        ]);
    }

    public function scopeByStatut($query, TicketStatut $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopeByPriorite($query, NiveauPriorite $priorite): Builder
    {
        return $query->where('niveau_priorite', $priorite);
    }

    public function scopeUrgents($query): Builder
    {
        return $query->where('niveau_priorite', NiveauPriorite::Urgence)
                     ->whereNotIn('statut', [
                         TicketStatut::DossierCloture,
                         TicketStatut::ClotureSatisfait,
                     ]);
    }

    public function scopeEnRetard($query): Builder
    {
        return $query->where(function ($q) {
            $q->where('niveau_priorite', NiveauPriorite::Urgence)
              ->where('date_creation', '<', now()->subMinutes(30))
              ->orWhere('niveau_priorite', NiveauPriorite::Prioritaire)
              ->where('date_creation', '<', now()->subMinutes(120))
              ->orWhere('niveau_priorite', NiveauPriorite::Standard)
              ->where('date_creation', '<', now()->subMinutes(480));
        })->whereNotIn('statut', [
            TicketStatut::DossierCloture,
            TicketStatut::ClotureSatisfait,
        ]);
    }

    public function scopeSansArtisan($query): Builder
    {
        return $query->whereNull('artisan_id')
                     ->whereIn('statut', [
                         TicketStatut::FicheComplete,
                         TicketStatut::RdvPlanifie,
                     ]);
    }

    public function scopeDuJour($query): Builder
    {
        return $query->whereDate('date_creation', today());
    }

    public function scopeDeLaSemaine($query): Builder
    {
        return $query->whereBetween('date_creation', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function estActif(): bool
    {
        return $this->statut->estActif();
    }

    public function estBloquant(): bool
    {
        return $this->statut->estBloquant();
    }

    public function estCloture(): bool
    {
        return in_array($this->statut, [
            TicketStatut::DossierCloture,
            TicketStatut::ClotureSatisfait,
        ]);
    }

    public function peutPasserA(TicketStatut $nouveauStatut): bool
    {
        return in_array($nouveauStatut, $this->statut->statutsSuivants());
    }

    public function changerStatut(TicketStatut $nouveauStatut, ?string $notes = null): void
    {
        if (!$this->peutPasserA($nouveauStatut)) {
            throw new \Exception("Transition impossible de {$this->statut->value} à {$nouveauStatut->value}");
        }

        $data = ['statut' => $nouveauStatut];

        if ($notes) {
            $data['notes'] = $this->notes . "\n[" . now()->format('d/m/Y H:i') . "] {$notes}";
        }

        if (in_array($nouveauStatut, [TicketStatut::DossierCloture, TicketStatut::ClotureSatisfait])) {
            $data['date_cloture'] = now();
        }

        $this->update($data);
    }

    public function assignerArtisan(Artisan $artisan): void
    {
        if (!$artisan->estDisponible()) {
            throw new \Exception("L'artisan n'est pas disponible");
        }

        $this->update(['artisan_id' => $artisan->id]);
    }

    public function planifierRDV(\DateTime $dateRdv): void
    {
        $this->update([
            'statut' => TicketStatut::RdvPlanifie,
            'rdv_planifie_at' => $dateRdv,
        ]);
    }

    public function programmerRappel(\DateTime $dateRappel): void
    {
        $this->update([
            'statut' => TicketStatut::RappelPromis,
            'rappel_promise_at' => $dateRappel,
        ]);
    }

    public function completeterFiche(array $data): FicheP2
    {
        $fiche = $this->ficheP2()->create($data);

        if ($fiche->fiche_complete) {
            $this->changerStatut(TicketStatut::FicheComplete, 'Fiche P2 complétée');
        } else {
            $this->changerStatut(TicketStatut::FicheIncomplete, 'Fiche P2 incomplète');
        }

        return $fiche;
    }

    public function necessiteP8(): bool
    {
        return $this->rapportSatisfaction && $this->rapportSatisfaction->note_nps <= 5;
    }

    public function getDelaiRestantSLA(): int
    {
        if (!$this->niveau_priorite || $this->estCloture()) {
            return 0;
        }

        $delaiMax = $this->niveau_priorite->delaiMaxMinutes();
        $ecoule = $this->date_creation->diffInMinutes(now());

        return max(0, $delaiMax - $ecoule);
    }

    public function getSlaDepasseDepuis(): ?string
    {
        if ($this->sla_respecte) {
            return null;
        }

        $delaiMax = $this->niveau_priorite->delaiMaxMinutes();
        $depassement = $this->date_creation->addMinutes($delaiMax);

        return $depassement->diffForHumans();
    }

    // ── Méthodes statiques ──────────────────────────────────────────
    public static function genererReference(): string
    {
        $count = static::whereYear('created_at', now()->year)->count() + 1;
        return 'TK-' . now()->year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
    }

    public static function getKpis(): array
    {
        return [
            'total_jour' => static::duJour()->count(),
            'actifs' => static::actifs()->count(),
            'urgents' => static::urgents()->count(),
            'en_retard' => static::enRetard()->count(),
            'sans_artisan' => static::sansArtisan()->count(),
            'clotures_jour' => static::clotures()->duJour()->count(),
            'taux_satisfaction' => static::getTauxSatisfaction(),
            'delai_moyen_minutes' => static::getDelaiMoyen(),
        ];
    }

    public static function getTauxSatisfaction(): float
    {
        $total = static::clotures()->whereHas('rapportSatisfaction')->count();
        if ($total === 0) return 0;

        $satisfaits = static::clotures()
            ->whereHas('rapportSatisfaction', function ($q) {
                $q->where('note_nps', '>=', 8);
            })->count();

        return round(($satisfaits / $total) * 100, 1);
    }

    public static function getDelaiMoyen(): float
    {
        return static::clotures()
            ->whereNotNull('date_cloture')
            ->get()
            ->avg(function ($ticket) {
                return $ticket->date_creation->diffInMinutes($ticket->date_cloture);
            }) ?? 0;
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket) {
            if (empty($ticket->reference)) {
                $ticket->reference = static::genererReference();
            }
            if (empty($ticket->date_creation)) {
                $ticket->date_creation = now();
            }
            if (empty($ticket->statut)) {
                $ticket->statut = TicketStatut::AppelRecu;
            }
        });

        static::updating(function (Ticket $ticket) {
            // Si clôture, enregistrer la date
            if ($ticket->isDirty('statut') &&
                in_array($ticket->statut, [TicketStatut::DossierCloture, TicketStatut::ClotureSatisfait]) &&
                !$ticket->date_cloture) {
                $ticket->date_cloture = now();
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function contactParticulier()
    {
        return $this->belongsTo(ContactParticulier::class);
    }

    public function artisan()
    {
        return $this->belongsTo(Artisan::class);
    }

    public function operateur()
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    public function ficheP2()
    {
        return $this->hasOne(FicheP2::class);
    }

    public function rapportsSatisfaction()
    {
        return $this->hasMany(RapportSatisfactionP6::class);
    }

    public function rapportSatisfaction()
    {
        return $this->hasOne(RapportSatisfactionP6::class)->latestOfMany();
    }

    public function reclamations()
    {
        return $this->hasMany(ReclamationP8::class);
    }

    public function reclamation()
    {
        return $this->hasOne(ReclamationP8::class)->latestOfMany();
    }

    public function reclamationActive()
    {
        return $this->hasOne(ReclamationP8::class)
            ->whereIn('statut', ['ouverte', 'en_traitement']);
    }
}
