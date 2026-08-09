<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'commentable_type',
        'commentable_id',
        'contenu',
        'parent_id',
    ];

    // ── Relations ────────────────────────────────────────────────────
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function parent()
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    // ── Scopes ──────────────────────────────────────────────────────
    public function scopeRacines($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePourUtilisateur($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ── Méthodes métier ─────────────────────────────────────────────
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function hasReplies(): bool
    {
        return $this->replies()->count() > 0;
    }
}
