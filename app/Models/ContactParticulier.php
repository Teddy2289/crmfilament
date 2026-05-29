<?php

namespace App\Models;

use App\Enums\TypeLogement;
use App\Enums\StatutOccupant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class ContactParticulier extends Model
{
    protected $table = 'contact_particuliers';

    protected $casts = [
        'type_logement' => TypeLogement::class,
        'statut_occupant' => StatutOccupant::class,
    ];

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'email',
        'adresse_complete',
        'type_logement',
        'statut_occupant',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getNomCompletAttribute(): string
    {
        return trim(($this->prenom ? $this->prenom . ' ' : '') . $this->nom);
    }

    public function getInitialesAttribute(): string
    {
        return strtoupper(
            ($this->prenom ? substr($this->prenom, 0, 1) : '') .
                substr($this->nom, 0, 1)
        );
    }

    public function getTypeLogementLabelAttribute(): string
    {
        return $this->type_logement?->label() ?? 'Non défini';
    }

    public function getTypeLogementColorAttribute(): string
    {
        return $this->type_logement?->color() ?? 'gray';
    }

    public function getTypeLogementIconAttribute(): string
    {
        return $this->type_logement?->icon() ?? 'heroicon-o-home';
    }

    public function getStatutOccupantLabelAttribute(): string
    {
        return $this->statut_occupant?->label() ?? 'Non défini';
    }

    public function getStatutOccupantColorAttribute(): string
    {
        return $this->statut_occupant?->color() ?? 'gray';
    }

    public function getStatutOccupantIconAttribute(): string
    {
        return $this->statut_occupant?->icon() ?? 'heroicon-o-user';
    }

    public function getEstProprietaireAttribute(): bool
    {
        return $this->statut_occupant === StatutOccupant::Proprietaire;
    }

    public function getEstLocataireAttribute(): bool
    {
        return $this->statut_occupant === StatutOccupant::Locataire;
    }

    public function getEstGestionnaireAttribute(): bool
    {
        return $this->statut_occupant === StatutOccupant::Gestionnaire;
    }

    public function getNombreTicketsAttribute(): int
    {
        return $this->tickets()->count();
    }

    public function getDernierTicketAttribute(): ?Ticket
    {
        return $this->tickets()->latest()->first();
    }

    public function getDernierContactAttribute(): ?string
    {
        $dernierTicket = $this->dernier_ticket;
        return $dernierTicket?->date_creation?->diffForHumans();
    }

    public function getEstFideleAttribute(): bool
    {
        return $this->tickets()->count() >= 3;
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function mettreAJourLogement(TypeLogement $type, StatutOccupant $statut = null): void
    {
        $data = ['type_logement' => $type];

        if ($statut) {
            $data['statut_occupant'] = $statut;
        }

        $this->update($data);
    }

    public function aUnTicketEnCours(): bool
    {
        return $this->tickets()
            ->whereNotIn('statut', ['dossier_cloture', 'cloture_satisfait'])
            ->exists();
    }

    public function getTicketsActifs()
    {
        return $this->tickets()
            ->whereNotIn('statut', ['dossier_cloture', 'cloture_satisfait'])
            ->get();
    }

    public function getHistoriqueTickets(int $limite = 10)
    {
        return $this->tickets()
            ->with(['artisan', 'ficheP2'])
            ->latest()
            ->take($limite)
            ->get();
    }

    public function getTauxSatisfactionAttribute(): ?float
    {
        $ticketsAvecRapport = $this->tickets()
            ->whereHas('rapportSatisfaction')
            ->with('rapportSatisfaction')
            ->get();

        if ($ticketsAvecRapport->isEmpty()) {
            return null;
        }

        return round($ticketsAvecRapport->avg(function ($ticket) {
            return $ticket->rapportSatisfaction->note_nps;
        }), 1);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeProprietaires($query): Builder
    {
        return $query->where('statut_occupant', StatutOccupant::Proprietaire);
    }

    public function scopeLocataires($query): Builder
    {
        return $query->where('statut_occupant', StatutOccupant::Locataire);
    }

    public function scopeGestionnaires($query): Builder
    {
        return $query->where('statut_occupant', StatutOccupant::Gestionnaire);
    }

    public function scopeParTypeLogement($query, TypeLogement $type): Builder
    {
        return $query->where('type_logement', $type);
    }

    public function scopeMaisons($query): Builder
    {
        return $query->where('type_logement', TypeLogement::Maison);
    }

    public function scopeAppartements($query): Builder
    {
        return $query->where('type_logement', TypeLogement::Appartement);
    }

    public function scopeLocauxCommerciaux($query): Builder
    {
        return $query->where('type_logement', TypeLogement::LocalCommercial);
    }

    public function scopeAvecTicketsActifs($query): Builder
    {
        return $query->whereHas('tickets', function ($q) {
            $q->whereNotIn('statut', ['dossier_cloture', 'cloture_satisfait']);
        });
    }

    public function scopeFideles($query): Builder
    {
        return $query->has('tickets', '>=', 3);
    }

    public function scopeNouveaux($query): Builder
    {
        return $query->doesntHave('tickets');
    }

    public function scopeRecents($query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeParVille($query, string $ville): Builder
    {
        return $query->where('adresse_complete', 'like', "%{$ville}%");
    }

    public function scopeSansTicketDepuis($query, int $jours): Builder
    {
        return $query->whereDoesntHave('tickets', function ($q) use ($jours) {
            $q->where('created_at', '>=', now()->subDays($jours));
        });
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(): array
    {
        return [
            'total' => static::count(),
            'nouveaux_mois' => static::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'avec_tickets_actifs' => static::avecTicketsActifs()->count(),
            'fideles' => static::fideles()->count(),
            'par_type_logement' => static::getRepartitionParTypeLogement(),
            'par_statut_occupant' => static::getRepartitionParStatutOccupant(),
        ];
    }

    public static function getRepartitionParTypeLogement(): array
    {
        return collect(TypeLogement::cases())
            ->mapWithKeys(function ($type) {
                return [$type->value => static::where('type_logement', $type)->count()];
            })
            ->toArray();
    }

    public static function getRepartitionParStatutOccupant(): array
    {
        return collect(StatutOccupant::cases())
            ->mapWithKeys(function ($statut) {
                return [$statut->value => static::where('statut_occupant', $statut)->count()];
            })
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (ContactParticulier $contact) {
            // Normaliser le téléphone
            if ($contact->telephone) {
                $contact->telephone = preg_replace('/[^0-9+]/', '', $contact->telephone);
            }
        });

        static::updating(function (ContactParticulier $contact) {
            if ($contact->isDirty('telephone')) {
                $contact->telephone = preg_replace('/[^0-9+]/', '', $contact->telephone);
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'contact_particulier_id');
    }

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
}
