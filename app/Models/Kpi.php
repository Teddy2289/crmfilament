<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Kpi extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'type',
        'model',
        'field',
        'filters',
        'aggregation_period',
        'user_id',
        'public',
        'actif',
        'target_value',
        'target_operator',
    ];

    protected $casts = [
        'filters' => 'array',
        'public' => 'boolean',
        'actif' => 'boolean',
        'target_value' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopePublic($query)
    {
        return $query->where('public', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('public', true)
              ->orWhere('user_id', $userId);
        });
    }

    public function calculateValue(): float
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // Appliquer les filtres
        if ($this->filters && is_array($this->filters)) {
            foreach ($this->filters as $field => $value) {
                $query->where($field, $value);
            }
        }

        // Appliquer la période d'agrégation
        $query = $this->applyAggregationPeriod($query);

        return match($this->type) {
            'count' => $query->count(),
            'sum' => $query->sum($this->field),
            'average' => $query->avg($this->field),
            'percentage' => $this->calculatePercentage($query),
            default => 0,
        };
    }

    protected function getModelClass(): string
    {
        return match($this->model) {
            'prospect' => Prospect::class,
            'client' => Client::class,
            'partenaire' => Partenaire::class,
            'opportunite' => Opportunite::class,
            'rendez_vous' => RendezVous::class,
            'appel' => Appel::class,
            default => Prospect::class,
        };
    }

    protected function applyAggregationPeriod($query)
    {
        return match($this->aggregation_period) {
            'daily' => $query->whereDate('created_at', today()),
            'weekly' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'monthly' => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year),
            'yearly' => $query->whereYear('created_at', now()->year),
            default => $query,
        };
    }

    protected function calculatePercentage($query): float
    {
        $total = $this->getModelClass()::count();
        if ($total === 0) return 0;

        $count = $query->count();
        return round(($count / $total) * 100, 2);
    }

    public function isTargetMet(): bool
    {
        if ($this->target_value === null) return true;

        $value = $this->calculateValue();

        return match($this->target_operator) {
            '>=' => $value >= $this->target_value,
            '<=' => $value <= $this->target_value,
            '=' => $value == $this->target_value,
            '>' => $value > $this->target_value,
            '<' => $value < $this->target_value,
            default => true,
        };
    }

    public function getProgressPercentage(): float
    {
        if ($this->target_value === null || $this->target_value == 0) return 0;

        $value = $this->calculateValue();
        return min(round(($value / $this->target_value) * 100, 2), 100);
    }
}
