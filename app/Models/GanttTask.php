<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GanttTask extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom',
        'description',
        'project_id',
        'parent_id',
        'assigned_to',
        'prospect_id',
        'client_id',
        'partenaire_id',
        'opportunite_id',
        'start_date',
        'end_date',
        'duration',
        'progress',
        'status',
        'order',
        'milestone',
        'color',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'order' => 'integer',
        'milestone' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GanttTask::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GanttTask::class, 'parent_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDelayed($query)
    {
        return $query->where('status', 'delayed');
    }

    public function scopeMilestones($query)
    {
        return $query->where('milestone', true);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_date', [$startDate, $endDate])
            ->orWhereBetween('end_date', [$startDate, $endDate]);
    }

    public function isOverdue(): bool
    {
        return $this->end_date < now() && !$this->isCompleted();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed' || $this->progress === 100;
    }

    public function calculateDuration(): int
    {
        if ($this->start_date && $this->end_date) {
            return $this->start_date->diffInDays($this->end_date);
        }
        return 0;
    }

    public function updateProgress(int $progress): void
    {
        $this->update([
            'progress' => min(max($progress, 0), 100),
            'status' => $progress === 100 ? 'completed' : ($progress > 0 ? 'in_progress' : 'pending'),
        ]);
    }
}
