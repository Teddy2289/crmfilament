<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactPartenaire extends Model
{
    use SoftDeletes;

    protected $table = 'contact_partenaires';

    protected $casts = [
        'date_naissance' => 'date',
        'est_principal' => 'boolean',
        'est_decisionnaire' => 'boolean',
    ];

    protected $fillable = [
        'partenaire_id',
        'civilite',
        'nom',
        'prenom',
        'fonction',
        'service',
        'email',
        'telephone_direct',
        'telephone_mobile',
        'telephone_perso',
        'email_perso',
        'date_naissance',
        'notes',
        'est_principal',
        'est_decisionnaire',
        'niveau_influence',
        'canal_prefere',
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getNomCompletAttribute(): string
    {
        return trim(
            ($this->civilite ? $this->civilite . ' ' : '') .
            $this->prenom . ' ' .
            $this->nom
        );
    }

    public function getNomAffichageAttribute(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getInitialesAttribute(): string
    {
        return strtoupper(
            substr($this->prenom, 0, 1) .
            substr($this->nom, 0, 1)
        );
    }

    public function getFonctionCompleteAttribute(): string
    {
        $parts = [];

        if ($this->fonction) {
            $parts[] = $this->fonction;
        }

        if ($this->service) {
            $parts[] = $this->service;
        }

        return implode(' - ', $parts);
    }

    public function getTelephonePrincipalAttribute(): string
    {
        return $this->telephone_direct ?? $this->telephone_mobile ?? 'N/A';
    }

    public function getEmailPrincipalAttribute(): string
    {
        return $this->email ?? $this->email_perso ?? 'N/A';
    }

    public function getEstContactableAttribute(): bool
    {
        return !empty($this->telephone_direct) ||
               !empty($this->telephone_mobile) ||
               !empty($this->email);
    }

    public function getNiveauInfluenceLabelAttribute(): string
    {
        return match($this->niveau_influence) {
            1 => 'Faible',
            2 => 'Moyen',
            3 => 'Fort',
            4 => 'Très fort',
            5 => 'Décisionnaire',
            default => 'Non défini',
        };
    }

    public function getNiveauInfluenceColorAttribute(): string
    {
        return match($this->niveau_influence) {
            1 => 'gray',
            2 => 'info',
            3 => 'warning',
            4 => 'orange',
            5 => 'danger',
            default => 'gray',
        };
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function definirCommePrincipal(): void
    {
        // Retirer le statut principal des autres contacts du même partenaire
        static::where('partenaire_id', $this->partenaire_id)
            ->where('id', '!=', $this->id)
            ->update(['est_principal' => false]);

        $this->update(['est_principal' => true]);
    }

    public function definirCommeDecisionnaire(): void
    {
        $this->update([
            'est_decisionnaire' => true,
            'niveau_influence' => 5,
        ]);
    }

    public function mettreAJourCoordonnees(array $data): void
    {
        $allowedFields = [
            'email', 'telephone_direct', 'telephone_mobile',
            'telephone_perso', 'email_perso',
        ];

        $this->update(array_intersect_key($data, array_flip($allowedFields)));
    }

    public function ajouterNote(string $note): void
    {
        $this->update([
            'notes' => $this->notes
                ? $this->notes . "\n[" . now()->format('d/m/Y H:i') . "] {$note}"
                : "[". now()->format('d/m/Y H:i') . "] {$note}",
        ]);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopePrincipaux($query)
    {
        return $query->where('est_principal', true);
    }

    public function scopeDecisionnaires($query)
    {
        return $query->where('est_decisionnaire', true);
    }

    public function scopeParFonction($query, string $fonction)
    {
        return $query->where('fonction', 'like', "%{$fonction}%");
    }

    public function scopeParService($query, string $service)
    {
        return $query->where('service', $service);
    }

    public function scopeContactables($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('email')
              ->orWhereNotNull('telephone_direct')
              ->orWhereNotNull('telephone_mobile');
        });
    }

    public function scopeParNiveauInfluence($query, int $niveau)
    {
        return $query->where('niveau_influence', '>=', $niveau);
    }

    public function scopePourPartenaire($query, int $partenaireId)
    {
        return $query->where('partenaire_id', $partenaireId);
    }

    // ── Boot ────────────────────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (ContactPartenaire $contact) {
            // Si premier contact du partenaire, le définir comme principal
            if (!static::where('partenaire_id', $contact->partenaire_id)->exists()) {
                $contact->est_principal = true;
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────
    public function partenaire()
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function appels()
    {
        return $this->morphMany(Appel::class, 'appelable');
    }

    public function rendezVous()
    {
        return $this->morphMany(RendezVous::class, 'rdvable');
    }
}
