<?php

namespace App\Models;

use App\Enums\StatutClotureP6;
use App\Enums\TicketStatut;
use App\Enums\StatutReclamation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RapportSatisfactionP6 extends Model
{
    protected $table = 'rapport_satisfaction_p6s';

    protected $casts = [
        'statut_cloture' => StatutClotureP6::class,
        'date_appel_j1' => 'date',
        'note_nps' => 'integer',
        'feedback_artisan' => 'boolean',
    ];

    protected $fillable = [
        'ticket_id',
        'artisan_id',
        'operateur_id',
        'date_appel_j1',
        'note_nps',
        'verbatim_client',
        'feedback_artisan',
        'statut_cloture',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getClassificationNPS(): string
    {
        return match (true) {
            $this->note_nps >= 9 => 'Promoteur',
            $this->note_nps >= 7 => 'Passif',
            default => 'Détracteur',
        };
    }

    public function getClassificationColorAttribute(): string
    {
        return match ($this->getClassificationNPS()) {
            'Promoteur' => 'success',
            'Passif' => 'warning',
            'Détracteur' => 'danger',
            default => 'gray',
        };
    }

    public function getClassificationIconAttribute(): string
    {
        return match ($this->getClassificationNPS()) {
            'Promoteur' => 'heroicon-o-face-smile',
            'Passif' => 'heroicon-o-face-meh',
            'Détracteur' => 'heroicon-o-face-frown',
            default => 'heroicon-o-question-mark-circle',
        };
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut_cloture?->label() ?? 'Non défini';
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut_cloture?->color() ?? 'gray';
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function declencheP8(): bool
    {
        return $this->note_nps <= 5;
    }

    public function necessiteSuiviQualite(): bool
    {
        return in_array($this->note_nps, [6, 7]);
    }

    public function necessiteP8(): bool
    {
        return $this->statut_cloture?->necessiteP8() ?? $this->declencheP8();
    }

    public function estSatisfait(): bool
    {
        return $this->note_nps >= 8;
    }

    public function updateNoteArtisan(): void
    {
        if (!$this->artisan_id) {
            return;
        }

        $moyenne = static::where('artisan_id', $this->artisan_id)
            ->whereNotNull('note_nps')
            ->avg('note_nps');

        if ($this->artisan) {
            $this->artisan->update([
                'note_moyenne' => round($moyenne, 2)
            ]);
        }
    }

    public function ouvrirReclamationP8(): ?ReclamationP8
    {
        if (!$this->declencheP8()) {
            return null;
        }

        // Vérifier si le ticket existe
        if (!$this->ticket) {
            return null;
        }

        // Vérifier si une réclamation existe déjà
        if ($this->ticket->reclamationActive) {
            return $this->ticket->reclamationActive;
        }

        $businessDays = $this->calculerDateResolution();

        $reclamation = ReclamationP8::create([
            'ticket_id' => $this->ticket_id,
            'rapport_satisfaction_id' => $this->id,
            'date_ouverture' => now(),
            'description_reclamation' => $this->genererDescriptionReclamation(),
            'statut' => StatutReclamation::Ouverte,
            'date_resolution_cible' => $businessDays->toDateString(),
        ]);

        // Mise à jour statut ticket
        if ($this->ticket) {
            try {
                $this->ticket->changerStatut(
                    TicketStatut::ReclamationOuverte,
                    "P8 ouvert automatiquement - NPS: {$this->note_nps}/10"
                );
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error('Erreur changement statut ticket: ' . $e->getMessage());
            }
        }

        return $reclamation;
    }

    protected function genererDescriptionReclamation(): string
    {
        $description = "Réclamation automatique - NPS ≤ 5\n";
        $description .= "Note: {$this->note_nps}/10\n";
        $description .= "Classification: {$this->getClassificationNPS()}\n";

        if ($this->verbatim_client) {
            $description .= "Verbatim client: {$this->verbatim_client}\n";
        }

        if ($this->artisan) {
            $description .= "Artisan: {$this->artisan->nom_complet}\n";
        }

        if ($this->date_appel_j1) {
            $description .= "Date appel J+1: {$this->date_appel_j1->format('d/m/Y')}";
        }

        return $description;
    }

    protected function calculerDateResolution(): \Carbon\Carbon
    {
        $date = now()->copy();
        $joursAjoutes = 0;

        while ($joursAjoutes < 5) {
            $date->addDay();
            if (!$date->isWeekend()) {
                $joursAjoutes++;
            }
        }

        return $date;
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePromoteurs($query)
    {
        return $query->where('note_nps', '>=', 9);
    }

    public function scopePassifs($query)
    {
        return $query->whereBetween('note_nps', [7, 8]);
    }

    public function scopeDetracteurs($query)
    {
        return $query->where('note_nps', '<=', 6);
    }

    public function scopeSatisfaits($query)
    {
        return $query->where('note_nps', '>=', 8);
    }

    public function scopeAvecReclamation($query)
    {
        return $query->whereHas('reclamation');
    }

    public function scopeSansReclamation($query)
    {
        return $query->whereDoesntHave('reclamation');
    }

    public function scopeDuMois($query)
    {
        return $query->whereMonth('date_appel_j1', now()->month)
                     ->whereYear('date_appel_j1', now()->year);
    }

    // ── Méthodes statiques KPIs ─────────────────────────────────────
    public static function getNPSMoyen(): float
    {
        return round(static::whereNotNull('note_nps')->avg('note_nps') ?? 0, 1);
    }

    public static function getTauxSatisfaction(): float
    {
        $total = static::count();
        if ($total === 0) return 0;

        $satisfaits = static::satisfaits()->count();
        return round(($satisfaits / $total) * 100, 1);
    }

    public static function getRepartitionNPS(): array
    {
        return [
            'promoteurs' => static::promoteurs()->count(),
            'passifs' => static::passifs()->count(),
            'detracteurs' => static::detracteurs()->count(),
            'total' => static::count(),
        ];
    }

    public static function getEvolutionNPS(int $mois = 6): array
    {
        return collect(range($mois - 1, 0))
            ->map(function ($i) {
                $date = now()->subMonths($i);
                return [
                    'mois' => $date->format('Y-m'),
                    'label' => $date->format('M Y'),
                    'moyenne' => static::whereYear('date_appel_j1', $date->year)
                        ->whereMonth('date_appel_j1', $date->month)
                        ->avg('note_nps') ?? 0,
                    'total' => static::whereYear('date_appel_j1', $date->year)
                        ->whereMonth('date_appel_j1', $date->month)
                        ->count(),
                ];
            })
            ->toArray();
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::saving(function (RapportSatisfactionP6 $rapport) {
            // Auto-statut selon NPS
            if ($rapport->note_nps !== null && !$rapport->statut_cloture) {
                $rapport->statut_cloture = StatutClotureP6::depuisNPS($rapport->note_nps);
            }
        });

        static::created(function (RapportSatisfactionP6 $rapport) {
            // Mise à jour note artisan
            $rapport->updateNoteArtisan();

            // Ouverture P8 automatique si NPS ≤ 5
            if ($rapport->declencheP8()) {
                $rapport->ouvrirReclamationP8();
            }
        });

        static::updated(function (RapportSatisfactionP6 $rapport) {
            // Si la note a changé, mettre à jour l'artisan
            if ($rapport->isDirty('note_nps')) {
                $rapport->updateNoteArtisan();

                // Vérifier si on doit ouvrir P8 maintenant
                if ($rapport->declencheP8() && $rapport->ticket && !$rapport->ticket->reclamationActive) {
                    $rapport->ouvrirReclamationP8();
                }
            }
        });
    }

    // ── Relations Typées ─────────────────────────────────────────────
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function artisan(): BelongsTo
    {
        return $this->belongsTo(Artisan::class, 'artisan_id');
    }

    public function operateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operateur_id');
    }

    public function reclamation(): HasOne
    {
        return $this->hasOne(ReclamationP8::class, 'rapport_satisfaction_id');
    }
}
