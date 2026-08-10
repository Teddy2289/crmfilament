<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    protected $fillable = [
        'nom',
        'description',
        'type',
        'statut',
        'date_debut',
        'date_fin',
        'budget',
        'budget_depense',
        'created_by',
        'assigned_to',
        'cibles',
        'contenu',
        'envois_total',
        'ouvertures',
        'clics',
        'conversions',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'budget' => 'decimal:2',
        'budget_depense' => 'decimal:2',
        'cibles' => 'array',
        'contenu' => 'array',
        'envois_total' => 'integer',
        'ouvertures' => 'integer',
        'clics' => 'integer',
        'conversions' => 'integer',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeDraft($query)
    {
        return $query->where('statut', 'draft');
    }

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('statut', 'paused');
    }

    public function scopeCompleted($query)
    {
        return $query->where('statut', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('statut', 'cancelled');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('created_by', $userId);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeRunning($query)
    {
        return $query->where('statut', 'active')
            ->where('date_debut', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', now());
            });
    }

    public function isActive(): bool
    {
        return $this->statut === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->statut === 'completed';
    }

    public function getBudgetRemainingAttribute(): float
    {
        return ($this->budget ?? 0) - $this->budget_depense;
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->envois_total === 0) {
            return 0;
        }
        return ($this->ouvertures / $this->envois_total) * 100;
    }

    public function getClickRateAttribute(): float
    {
        if ($this->ouvertures === 0) {
            return 0;
        }
        return ($this->clics / $this->ouvertures) * 100;
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->clics === 0) {
            return 0;
        }
        return ($this->conversions / $this->clics) * 100;
    }

    public function getStatutLabelAttribute(): string
    {
        return match($this->statut) {
            'draft' => 'Brouillon',
            'active' => 'Active',
            'paused' => 'En pause',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => ucfirst($this->statut),
        };
    }

    public function getStatutColorAttribute(): string
    {
        return match($this->statut) {
            'draft' => 'gray',
            'active' => 'success',
            'paused' => 'warning',
            'completed' => 'primary',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'email' => 'Email',
            'sms' => 'SMS',
            'social' => 'Réseaux sociaux',
            'print' => 'Impression',
            'event' => 'Événement',
            default => ucfirst($this->type),
        };
    }
}
