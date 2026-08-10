<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    protected $fillable = [
        'name',
        'type',
        'status',
        'file_path',
        'file_size',
        'created_by',
        'description',
        'tables',
        'started_at',
        'completed_at',
        'error_message',
        'automatic',
    ];

    protected $casts = [
        'tables' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'automatic' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeAutomatic($query)
    {
        return $query->where('automatic', true);
    }

    public function scopeManual($query)
    {
        return $query->where('automatic', false);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }
        return $this->started_at->diffForHumans($this->completed_at, true);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }
        
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'running' => 'En cours',
            'completed' => 'Terminé',
            'failed' => 'Échoué',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'running' => 'primary',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'full' => 'Complet',
            'incremental' => 'Incrémental',
            'differential' => 'Différentiel',
            default => ucfirst($this->type),
        };
    }
}
