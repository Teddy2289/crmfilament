<?php

namespace App\Models;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RendezVous extends Model
{
    use SoftDeletes;

    protected $table = 'rendez_vous';

    protected $casts = [
        'type' => RendezVousType::class,
        'statut' => RendezVousStatut::class,
        'date_heure' => 'datetime',
        'email_confirmation_envoye' => 'boolean',
        'email_invitation_envoye' => 'boolean',
    ];

    protected $fillable = [
        'rdvable_type',
        'rdvable_id',
        'commercial_id',
        'teleprospecteur_id',
        'type',
        'statut',
        'date_heure',
        'lieu',
        'adresse_lieu',
        'interlocuteur_nom',
        'interlocuteur_tel',
        'interlocuteur_email',
        'notes',
        'pdf_recap',
        'enregistrement_audio',
        'email_confirmation_envoye',
        'email_invitation_envoye',
        'outlook_event_id',
        'google_event_id',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getTypeLabelAttribute(): string
    {
        return $this->type->label();
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type->color();
    }

    public function getTypeIconAttribute(): string
    {
        return $this->type->icon();
    }

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

    public function getDateHeureFormateeAttribute(): string
    {
        return $this->date_heure->format('d/m/Y H:i');
    }

    public function getDateFormateeAttribute(): string
    {
        return $this->date_heure->format('d/m/Y');
    }

    public function getHeureFormateeAttribute(): string
    {
        return $this->date_heure->format('H:i');
    }

    public function getJourSemaineAttribute(): string
    {
        $jours = [
            'Sunday' => 'Dimanche',
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
        ];

        return $jours[$this->date_heure->format('l')];
    }

    public function getDureeRestanteAttribute(): string
    {
        if ($this->date_heure->isPast()) {
            return 'Passé';
        }

        $diff = $this->date_heure->diffForHumans([
            'parts' => 2,
            'join' => true,
        ]);

        return 'Dans '.$diff;
    }

    public function getEstPasseAttribute(): bool
    {
        return $this->date_heure->isPast();
    }

    public function getEstAujourdhuiAttribute(): bool
    {
        return $this->date_heure->isToday();
    }

    public function getEstCetteSemaineAttribute(): bool
    {
        return $this->date_heure->isCurrentWeek();
    }

    public function getEstPlanifieAttribute(): bool
    {
        return $this->statut === RendezVousStatut::Planifie;
    }

    public function getEstRealiseAttribute(): bool
    {
        return $this->statut === RendezVousStatut::Realise;
    }

    public function getEstAnnuleAttribute(): bool
    {
        return $this->statut === RendezVousStatut::Annule;
    }

    public function getEstDecaleAttribute(): bool
    {
        return $this->statut === RendezVousStatut::Decale;
    }

    public function getEstActifAttribute(): bool
    {
        return in_array($this->statut, [
            RendezVousStatut::Planifie,
            RendezVousStatut::Decale,
        ]);
    }

    public function getLieuCompletAttribute(): string
    {
        if (! $this->lieu && ! $this->adresse_lieu) {
            return 'Non spécifié';
        }

        return trim($this->lieu.($this->adresse_lieu ? ' - '.$this->adresse_lieu : ''));
    }

    public function getEstSynchroOutlookAttribute(): bool
    {
        return ! empty($this->outlook_event_id);
    }

    public function getEstSynchroGoogleAttribute(): bool
    {
        return ! empty($this->google_event_id);
    }

    public function getEstSynchroCalendarAttribute(): bool
    {
        return $this->est_synchro_outlook || $this->est_synchro_google;
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function marquerRealise(?string $notes = null): void
    {
        $data = ['statut' => RendezVousStatut::Realise];

        if ($notes) {
            $data['notes'] = $this->notes
                ? $this->notes."\n[Réalisé] {$notes}"
                : "[Réalisé] {$notes}";
        }

        $this->update($data);
    }

    public function marquerAnnule(string $motif): void
    {
        $this->update([
            'statut' => RendezVousStatut::Annule,
            'notes' => $this->notes
                ? $this->notes."\n[Annulé] {$motif}"
                : "[Annulé] {$motif}",
        ]);
    }

    public function decaler(Carbon $nouvelleDate, ?string $motif = null): void
    {
        $data = [
            'statut' => RendezVousStatut::Decale,
            'date_heure' => $nouvelleDate,
        ];

        if ($motif) {
            $data['notes'] = $this->notes
                ? $this->notes."\n[Décalé au {$nouvelleDate->format('d/m/Y H:i')}] {$motif}"
                : "[Décalé au {$nouvelleDate->format('d/m/Y H:i')}] {$motif}";
        }

        $this->update($data);
    }

    public function confirmer(): void
    {
        $this->update([
            'email_confirmation_envoye' => true,
        ]);
    }

    public function envoyerInvitation(): void
    {
        $this->update([
            'email_invitation_envoye' => true,
        ]);
    }

    public function ajouterPDF(string $path): void
    {
        $this->update(['pdf_recap' => $path]);
    }

    public function ajouterEnregistrement(string $path): void
    {
        $this->update(['enregistrement_audio' => $path]);
    }

    public function synchroniserOutlook(string $eventId): void
    {
        $this->update(['outlook_event_id' => $eventId]);
    }

    public function synchroniserGoogle(string $eventId): void
    {
        $this->update(['google_event_id' => $eventId]);
    }

    public function desynchroniserCalendar(): void
    {
        $this->update([
            'outlook_event_id' => null,
            'google_event_id' => null,
        ]);
    }

    public function ajouterNote(string $note): void
    {
        $this->update([
            'notes' => $this->notes
                ? $this->notes."\n[".now()->format('d/m/Y H:i')."] {$note}"
                : '['.now()->format('d/m/Y H:i')."] {$note}",
        ]);
    }

    public function assignerCommercial(int $userId): void
    {
        $this->update(['commercial_id' => $userId]);
    }

    public function assignerTeleprospecteur(int $userId): void
    {
        $this->update(['teleprospecteur_id' => $userId]);
    }

    public function mettreAJourInterlocuteur(
        string $nom,
        ?string $tel = null,
        ?string $email = null
    ): void {
        $this->update([
            'interlocuteur_nom' => $nom,
            'interlocuteur_tel' => $tel,
            'interlocuteur_email' => $email,
        ]);
    }

    /**
     * Vérifie les conflits de planning pour un utilisateur
     */
    public function aConflitAvec(RendezVous $autre): bool
    {
        return $this->date_heure->equalTo($autre->date_heure);
    }

    public static function verifierConflit(
        Carbon $date,
        int $userId,
        ?int $excludeId = null
    ): bool {
        $query = static::where(function ($q) use ($userId) {
            $q->where('commercial_id', $userId)
                ->orWhere('teleprospecteur_id', $userId);
        })
            ->where('date_heure', $date)
            ->whereIn('statut', [
                RendezVousStatut::Planifie->value,
                RendezVousStatut::Decale->value,
            ]);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePlanifies($query): Builder
    {
        return $query->where('statut', RendezVousStatut::Planifie);
    }

    public function scopeRealises($query): Builder
    {
        return $query->where('statut', RendezVousStatut::Realise);
    }

    public function scopeAnnules($query): Builder
    {
        return $query->where('statut', RendezVousStatut::Annule);
    }

    public function scopeDecales($query): Builder
    {
        return $query->where('statut', RendezVousStatut::Decale);
    }

    public function scopeActifs($query): Builder
    {
        return $query->whereIn('statut', [
            RendezVousStatut::Planifie->value,
            RendezVousStatut::Decale->value,
        ]);
    }

    public function scopeDuJour($query): Builder
    {
        return $query->whereDate('date_heure', today());
    }

    public function scopeDeDemain($query): Builder
    {
        return $query->whereDate('date_heure', today()->addDay());
    }

    public function scopeDeLaSemaine($query): Builder
    {
        return $query->whereBetween('date_heure', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeDuMois($query): Builder
    {
        return $query->whereMonth('date_heure', now()->month)
            ->whereYear('date_heure', now()->year);
    }

    public function scopeAVenir($query): Builder
    {
        return $query->where('date_heure', '>=', now());
    }

    public function scopePasses($query): Builder
    {
        return $query->where('date_heure', '<', now());
    }

    public function scopeParType($query, RendezVousType $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeAppels($query): Builder
    {
        return $query->where('type', RendezVousType::Appel);
    }

    public function scopePermanences($query): Builder
    {
        return $query->where('type', RendezVousType::Permanence);
    }

    public function scopePresentations($query): Builder
    {
        return $query->where('type', RendezVousType::Presentation);
    }

    public function scopeInterventions($query): Builder
    {
        return $query->where('type', RendezVousType::Intervention);
    }

    public function scopeParCommercial($query, int $userId): Builder
    {
        return $query->where('commercial_id', $userId);
    }

    public function scopeParTeleprospecteur($query, int $userId): Builder
    {
        return $query->where('teleprospecteur_id', $userId);
    }

    public function scopePourUtilisateur($query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('commercial_id', $userId)
                ->orWhere('teleprospecteur_id', $userId);
        });
    }

    public function scopeSansConfirmation($query): Builder
    {
        return $query->where('email_confirmation_envoye', false)
            ->where('statut', RendezVousStatut::Planifie)
            ->where('date_heure', '>=', now());
    }

    public function scopeSansInvitation($query): Builder
    {
        return $query->where('email_invitation_envoye', false)
            ->where('statut', RendezVousStatut::Planifie);
    }

    public function scopeSynchroOutlook($query): Builder
    {
        return $query->whereNotNull('outlook_event_id');
    }

    public function scopeSynchroGoogle($query): Builder
    {
        return $query->whereNotNull('google_event_id');
    }

    public function scopeNonSynchros($query): Builder
    {
        return $query->whereNull('outlook_event_id')
            ->whereNull('google_event_id');
    }

    public function scopeARappeler($query): Builder
    {
        return $query->where('statut', RendezVousStatut::Planifie)
            ->where('date_heure', '<', now())
            ->whereNull('outlook_event_id')
            ->whereNull('google_event_id');
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getKpis(?int $userId = null): array
    {
        $query = static::query();

        if ($userId) {
            $query->pourUtilisateur($userId);
        }

        return [
            'total_jour' => (clone $query)->duJour()->count(),
            'total_semaine' => (clone $query)->deLaSemaine()->count(),
            'planifies' => (clone $query)->planifies()->count(),
            'realises' => (clone $query)->realises()->count(),
            'annules' => (clone $query)->annules()->count(),
            'a_venir' => (clone $query)->aVenir()->count(),
            'taux_realisation' => static::getTauxRealisation($userId),
            'taux_annulation' => static::getTauxAnnulation($userId),
            'par_type' => static::getRepartitionParType($userId),
            'prochains' => (clone $query)->aVenir()
                ->orderBy('date_heure')
                ->take(5)
                ->get(),
        ];
    }

    public static function getTauxRealisation(?int $userId = null): float
    {
        $query = static::query();

        if ($userId) {
            $query->pourUtilisateur($userId);
        }

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $realises = (clone $query)->realises()->count();

        return round(($realises / $total) * 100, 1);
    }

    public static function getTauxAnnulation(?int $userId = null): float
    {
        $query = static::query();

        if ($userId) {
            $query->pourUtilisateur($userId);
        }

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $annules = (clone $query)->annules()->count();

        return round(($annules / $total) * 100, 1);
    }

    public static function getRepartitionParType(?int $userId = null): array
    {
        $query = static::query();

        if ($userId) {
            $query->pourUtilisateur($userId);
        }

        return collect(RendezVousType::cases())
            ->mapWithKeys(function ($type) use ($query) {
                return [$type->value => (clone $query)->where('type', $type)->count()];
            })
            ->toArray();
    }

    /**
     * Récupère l'agenda d'un utilisateur pour une période donnée
     */
    public static function getAgenda(int $userId, Carbon $debut, Carbon $fin): array
    {
        return static::pourUtilisateur($userId)
            ->whereBetween('date_heure', [$debut, $fin])
            ->whereIn('statut', [
                RendezVousStatut::Planifie->value,
                RendezVousStatut::Decale->value,
            ])
            ->orderBy('date_heure')
            ->get()
            ->map(function ($rdv) {
                return [
                    'id' => $rdv->id,
                    'title' => $rdv->type->label().' - '.($rdv->interlocuteur_nom ?? 'Sans interlocuteur'),
                    'start' => $rdv->date_heure->toIso8601String(),
                    'end' => $rdv->date_heure->addHour()->toIso8601String(),
                    'color' => $rdv->type->color(),
                    'icon' => $rdv->type->icon(),
                    'statut' => $rdv->statut->value,
                    'extendedProps' => [
                        'type' => $rdv->type->value,
                        'lieu' => $rdv->lieu,
                        'interlocuteur' => $rdv->interlocuteur_nom,
                    ],
                ];
            })
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (RendezVous $rdv) {
            if (! $rdv->statut) {
                $rdv->statut = RendezVousStatut::Planifie;
            }
            if (! $rdv->type) {
                $rdv->type = RendezVousType::Appel;
            }
        });

        static::updating(function (RendezVous $rdv) {
            // Si date modifiée, réinitialiser les synchronisations calendrier
            if ($rdv->isDirty('date_heure')) {
                $rdv->outlook_event_id = null;
                $rdv->google_event_id = null;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function rdvable()
    {
        return $this->morphTo();
    }

    public function commercial()
    {
        return $this->belongsTo(User::class, 'commercial_id');
    }

    public function teleprospecteur()
    {
        return $this->belongsTo(User::class, 'teleprospecteur_id');
    }

    // Relations polymorphiques pratiques
    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class, 'rdvable_id')
            ->where('rdvable_type', Partenaire::class);
    }

    public function prospect()
    {
        return $this->belongsTo(Prospect::class, 'rdvable_id')
            ->where('rdvable_type', Prospect::class);
    }

    public function artisan()
    {
        return $this->belongsTo(Artisan::class, 'rdvable_id')
            ->where('rdvable_type', Artisan::class);
    }

    public function contactParticulier()
    {
        return $this->belongsTo(ContactParticulier::class, 'rdvable_id')
            ->where('rdvable_type', ContactParticulier::class);
    }
}
