<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NoteCommentaire extends Model
{
    use SoftDeletes;

    protected $table = 'notes_commentaires';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'contexte' => 'array',
    ];

    protected $fillable = [
        'notable_type',
        'notable_id',
        'user_id',
        'type_note', // 'note', 'commentaire', 'suivi', 'rapport', etc.
        'contenu',
        'is_prive', // Pour différencier les notes privées des notes partagées
        'contexte', // Pour stocker des métadonnées contextuelles (ex: 'phoning', 'fiche', etc.)
    ];

    // ── Accesseurs ──────────────────────────────────────────────────
    public function getTypeNoteLabelAttribute(): string
    {
        return match ($this->type_note) {
            'note' => 'Note',
            'commentaire' => 'Commentaire',
            'suivi' => 'Suivi',
            'rapport' => 'Rapport',
            'phoning' => 'Note Phoning',
            'fiche' => 'Note Fiche',
            default => ucfirst($this->type_note ?? 'Note'),
        };
    }

    public function getTypeNoteColorAttribute(): string
    {
        return match ($this->type_note) {
            'note' => 'info',
            'commentaire' => 'primary',
            'suivi' => 'success',
            'rapport' => 'warning',
            'phoning' => 'purple',
            'fiche' => 'orange',
            default => 'gray',
        };
    }

    public function getEstPriveAttribute(): bool
    {
        return (bool) $this->is_prive;
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeNotes($query)
    {
        return $query->where('type_note', 'note');
    }

    public function scopeCommentaires($query)
    {
        return $query->where('type_note', 'commentaire');
    }

    public function scopeSuivis($query)
    {
        return $query->where('type_note', 'suivi');
    }

    public function scopePhoning($query)
    {
        return $query->where('type_note', 'phoning');
    }

    public function scopeFiche($query)
    {
        return $query->where('type_note', 'fiche');
    }

    public function scopePublics($query)
    {
        return $query->where('is_prive', false);
    }

    public function scopePrives($query)
    {
        return $query->where('is_prive', true);
    }

    public function scopeRecentes($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Relations ────────────────────────────────────────────────────
    public function notable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function marquerPrive(): void
    {
        $this->update(['is_prive' => true]);
    }

    public function marquerPublic(): void
    {
        $this->update(['is_prive' => false]);
    }

    public function changerType(string $nouveauType): void
    {
        $typesValides = ['note', 'commentaire', 'suivi', 'rapport', 'phoning', 'fiche'];
        
        if (! in_array($nouveauType, $typesValides)) {
            throw new \InvalidArgumentException("Type de note invalide : {$nouveauType}");
        }

        $this->update(['type_note' => $nouveauType]);
    }
}