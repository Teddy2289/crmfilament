<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    // Utilise 'custom_notifications' pour éviter le conflit avec la table Laravel native 'notifications'
    protected $table = 'custom_notifications';

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'lien',
        'lu',
        'lu_at',
    ];

    protected $casts = [
        'lu' => 'boolean',
        'lu_at' => 'datetime',
    ];

    const TYPES = [
        'task_due' => 'Tâche à faire',
        'task_overdue' => 'Tâche en retard',
        'workflow_step' => 'Workflow',
        'mention' => 'Mention',
        'system' => 'Système',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeNonLues($query)
    {
        return $query->where('lu', false);
    }

    public function scopeLues($query)
    {
        return $query->where('lu', true);
    }

    public function scopeParType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function marquerCommeLue(): void
    {
        $this->update([
            'lu' => true,
            'lu_at' => now(),
        ]);
    }

    public static function creer(int $userId, string $type, string $titre, string $message, ?string $lien = null): self
    {
        return self::create([
            'user_id' => $userId,
            'type' => $type,
            'titre' => $titre,
            'message' => $message,
            'lien' => $lien,
        ]);
    }
}
