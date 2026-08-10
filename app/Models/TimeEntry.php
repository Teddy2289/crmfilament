<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class TimeEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'task_id',
        'prospect_id',
        'client_id',
        'partenaire_id',
        'opportunite_id',
        'rendez_vous_id',
        'appel_id',
        'description',
        'start_time',
        'end_time',
        'duration',
        'type',
        'billable',
        'hourly_rate',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    public function opportunite(): BelongsTo
    {
        return $this->belongsTo(Opportunite::class);
    }

    public function rendezVous(): BelongsTo
    {
        return $this->belongsTo(RendezVous::class);
    }

    public function appel(): BelongsTo
    {
        return $this->belongsTo(Appel::class);
    }

    public function start(): void
    {
        $this->update([
            'start_time' => now(),
            'end_time' => null,
            'duration' => null,
        ]);
    }

    public function stop(): void
    {
        if ($this->start_time && !$this->end_time) {
            $endTime = now();
            $duration = $this->start_time->diffInMinutes($endTime);
            
            $this->update([
                'end_time' => $endTime,
                'duration' => $duration,
            ]);
        }
    }

    public function getDurationInHoursAttribute(): float
    {
        return $this->duration ? round($this->duration / 60, 2) : 0;
    }

    public function getCostAttribute(): float
    {
        if ($this->billable && $this->hourly_rate) {
            return round($this->duration_in_hours * $this->hourly_rate, 2);
        }
        return 0;
    }

    public function scopeRunning($query)
    {
        return $query->whereNotNull('start_time')->whereNull('end_time');
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('start_time')->whereNotNull('end_time');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    public function isRunning(): bool
    {
        return $this->start_time !== null && $this->end_time === null;
    }
}
